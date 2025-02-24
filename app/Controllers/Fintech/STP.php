<?php
	
	namespace App\Controllers\Fintech;
	
	use Exception;
	use App\Models\ConciliacionModel;
	use App\Models\OperationModel;
	use CodeIgniter\HTTP\ResponseInterface;
	use App\Controllers\PagesStatusCode;
	use App\Models\StpModel;
	
	class STP extends PagesStatusCode {
		public function __construct () {
			parent::__construct ();
			helper ( 'tools' );
			helper ( 'tools_helper' );
			helper ( 'tetraoctal_helper' );
		}
		/**
		 * @throws Exception
		 */
		public function doTransfer (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$stp = new StpModel();
			$beneficiarios = $this->input[ 'beneficiario' ];
			$responses = [];
			for ( $i = 0; $i < count ( $beneficiarios ); $i++ ) {
				$args[ 'beneficiario' ] = $beneficiarios[ $i ];
				$responses[] = json_decode ( $stp->sendDispersion ( $args, NULL, NULL, $this->user ), TRUE );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Orden enviada',
				'response'    => $responses,
			];
			$this->logResponse ( 67 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function testConsulta (): ResponseInterface {
			if ( $data = $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			$stp = new StpModel();
			$date = isset( $input[ 'date' ] ) ? strtotime ( $input[ 'date' ] ) : strtotime ( 'now' );
			return $this->getResponse ( json_decode ( $stp->sendConsulta ( $date, $input[ 'tOrden' ], $this->env ), TRUE ) );
		}
		/**
		 * Webhook para obtener la información de las transferencias por STP
		 * @return ResponseInterface|bool
		 */
		public function wbDispersion (): ResponseInterface|bool {
			$input = $this->getRequestLogin ( $this->request );
			if ( $data = $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$out = [
				'mensaje' => 'recibido' ];
			if ( !$this->logResponse ( 3, $this->input, $out ) ) {
				$this->serverError ( 'Proceso incompleto', 'No se logró guardar la información' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = $out;
			return $this->getResponse ( $this->responseBody );
		}
		public function AddMovement ( array $args, ?string $env = NULL ): array {
			$op = new OperationModel ();
			$res = $op->AddMovement ( $args, $env );
			if ( isset( $operation[ 0 ] ) ) {
				return [ FALSE, 'No se logró guardar el movimiento' ];
			}
			return [ 'id' => $res[ 'result' ] ];
		}
		public function rollback ( mixed $input, string $message, string $env ): ResponseInterface|array {
			$args = [
				'operationNumber' => $input[ 'reference_number' ],
				'transactionDate' => $input[ 'transactionDate' ],
				'trackingKey'     => $input[ 'tracking_key' ],
				'descriptor'      => $input[ 'descriptor' ],
				'opId'            => $input[ 'speid_id' ],
				'amount'          => $input[ 'amount' ],
				'sourceName'      => $input[ 'sourceName' ] ?? 'Desconocido',
				'sourceRfc'       => $input[ 'sourceRfc' ] ?? 'XAXX010101000',
				'sourceBank'      => $input[ 'sourceBank' ],
				'receiverName'    => $input[ 'receiverName' ] ?? 'Desconocido',
				'receiverRfc'     => $input[ 'receiverRfc' ] ?? 'XAXX010101000',
				'receiverBank'    => $input[ 'receiverBank' ],
			];
			$mov = $this->AddMovement ( $args, $env );
			if ( isset( $mov[ 0 ] ) ) {
				return $mov;
			}
			$stp = new StpModel();
			$data = [
				'folio'        => serialize32 ( [ 5, $mov[ 'id' ], 0, 0, strtotime ( 'now' ) ] ),
				'concepto'     => 'devolución de '.$args[ 'trackingKey' ],
				'refNumeric'   => MakeOperationNumber ( $mov[ 'id' ] ),
				'monto'        => $args[ 'amount' ],
				'empresa'      => 'WHITEFISH',
				'ordenante'    => [
					'clabe'  => $args[ 'receiverBank' ],
					'rfc'    => $args[ 'receiverRfc' ],
					'nombre' => $args[ 'receiverName' ],
				],
				'beneficiario' => [
					'clabe'  => $args[ 'sourceBank' ],
					'rfc'    => $args[ 'sourceRfc' ],
					'nombre' => $args[ 'sourceName' ],
				],
			];
			$rollback = json_decode ( $stp->sendDispersion ( $data, 'SANDBOX' ), TRUE );
			if ( isset( $rollback[ 'resultado' ][ 'descripcionError' ] ) ) {
				return $this->serverError ( 'No se logro realizar la devolución', $rollback );
			}
			$args = [
				'operationNumber' => $data[ 'refNumeric' ],
				'transactionDate' => date ( 'Y-m-d', strtotime ( 'now' ) ),
				'trackingKey'     => $data[ 'folio' ],
				'descriptor'      => $data[ 'concepto' ],
				'opId'            => $rollback[ 'resultado' ][ 'id' ],
				'amount'          => $data[ 'monto' ],
				'sourceName'      => $data[ 'ordenante' ][ 'nombre' ],
				'sourceRfc'       => $data[ 'ordenante' ] [ 'rfc' ],
				'sourceBank'      => $data[ 'ordenante' ][ 'clabe' ],
				'receiverName'    => $data[ 'beneficiario' ] [ 'nombre' ],
				'receiverRfc'     => $data[ 'beneficiario' ][ 'rfc' ],
				'receiverBank'    => $data[ 'beneficiario' ][ 'clabe' ],
			];
			$mov2 = $this->AddMovement ( $args, $env );
			if ( isset( $mov2[ 0 ] ) ) {
				return $mov2;
			}
			return $this->getResponse ( [ 'status' => 'correcto', "message" => $message ] );
		}
		/**
		 * Webhook para obtener la entrada de recursos en la cuenta de STP
		 * @return ResponseInterface|bool
		 */
		public function wbPayments (): ResponseInterface|bool {
			$input = $this->getRequestLogin ( $this->request );
			if ( $data = $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$out = [
				'mensaje' => 'confirmar' ];
			if ( !$this->logResponse ( 3, $this->input, $out ) ) {
				$this->serverError ( 'Proceso incompleto', 'No se logró guardar la información' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = $out;
			return $this->getResponse ( $this->responseBody );
		}
		public function wbReturns (): ResponseInterface|bool {
			$input = $this->getRequestLogin ( $this->request );
			if ( $data = $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$out = [
				'mensaje' => 'devolver',
				"id" => $input['id']];
			if ( !$this->logResponse ( 3, $this->input, $out ) ) {
				$this->serverError ( 'Proceso incompleto', 'No se logró guardar la información' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = $out;
			return $this->getResponse ( $this->responseBody );
		}
		public function doConciliationPlus ( array $operation, string $env ) {
			$conc = new ConciliacionModel();
		}
		public function makeConciliation ( array $operation, array $input, ?string $env = NULL ) {
		}
		public function makeConciliationPlus ( array $operation, array $input, string $env ) {
		}
		public function makeDispersion ( mixed $operation, mixed $input, string $env ) {
		}
	}