<?php
	
	namespace App\Controllers\Fintech;
	
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
		public function testCobro (): ResponseInterface {
			if ( $data = $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			$input[ 'folio' ] = serialize32 ( [ rand ( 1, 9 ), rand ( 1, 9 ), rand ( 1, 9 ), rand ( 1, 9 ), rand ( 1, 31221 ) ] );
			$input[ 'concepto' ] = 'devolucion';
			$input[ 'refNumeric' ] = MakeOperationNumber ( rand ( 1, 250 ) );
			$input[ 'empresa' ] = 'WHITEFISH';
			$stp = new StpModel();
			return $this->getResponse ( json_decode ( $stp->sendDispersion ( $input, 'SANDBOX' ), TRUE ) );
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
			if ( $data = $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			if ( !createLog ( "wbDispersion_stp_$this->env", json_encode ( $input ) ) ) {
				return $this->serverError ( 'Proceso incompleto', 'No se logró guardar la información' );
			}
			return $this->getResponse ( [ 'status' => 'correcto', "message" => "Información recibida y procesada correctamente" ] );
		}
		public function AddMovement ( array $args, string $env = NULL ): array {
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
				'trackingKey' => $input[ 'tracking_key' ],
				'descriptor' => $input[ 'descriptor' ],
				'opId' => $input[ 'speid_id' ],
				'amount' => $input[ 'amount' ],
				'sourceName' => $input[ 'sourceName' ] ?? 'Desconocido',
				'sourceRfc' => $input[ 'sourceRfc' ] ?? 'XAXX010101000',
				'sourceBank' => $input[ 'sourceBank' ],
				'receiverName' => $input[ 'receiverName' ] ?? 'Desconocido',
				'receiverRfc' => $input[ 'receiverRfc' ] ?? 'XAXX010101000',
				'receiverBank' => $input[ 'receiverBank' ],
			];
			$mov = $this->AddMovement ( $args, $env );
			if ( isset( $mov[ 0 ] ) ) {
				return $mov;
			}
			$stp = new StpModel();
			$data = [
				'folio' => serialize32 ( [ 5, $mov[ 'id' ], 0, 0, strtotime ( 'now' ) ] ),
				'concepto' => 'devolución de ' . $args[ 'trackingKey' ],
				'refNumeric' => MakeOperationNumber ( $mov[ 'id' ] ),
				'monto' => $args[ 'amount' ],
				'empresa' => 'WHITEFISH',
				'ordenante' => [
					'clabe' => $args[ 'receiverBank' ],
					'rfc' => $args[ 'receiverRfc' ],
					'nombre' => $args[ 'receiverName' ],
				],
				'beneficiario' => [
					'clabe' => $args[ 'sourceBank' ],
					'rfc' => $args[ 'sourceRfc' ],
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
				'trackingKey' => $data[ 'folio' ],
				'descriptor' => $data[ 'concepto' ],
				'opId' => $rollback[ 'resultado' ][ 'id' ],
				'amount' => $data[ 'monto' ],
				'sourceName' => $data[ 'ordenante' ][ 'nombre' ],
				'sourceRfc' => $data[ 'ordenante' ] [ 'rfc' ],
				'sourceBank' => $data[ 'ordenante' ][ 'clabe' ],
				'receiverName' => $data[ 'beneficiario' ] [ 'nombre' ],
				'receiverRfc' => $data[ 'beneficiario' ][ 'rfc' ],
				'receiverBank' => $data[ 'beneficiario' ][ 'clabe' ],
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
		public function wbAbonos (): mixed {
			if ( $data = $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			//Se crea el log con los datos que se recibieron por el webhook
			if ( !createLog ( "wbAbonos_stp_$this->env", json_encode ( $input ) ) ) {
				return $this->serverError ( 'Proceso incompleto', 'No se logró guardar la información' );
			}
			//validar que se tenga la descripcion o referencia numerica para poder validar
			$descriptor = $input[ 'descriptor' ] ?? NULL;
			$refNumber = $input[ 'reference_number' ] ?? NULL;
			$trackingKey = $input[ 'tracking_key' ] ?? NULL;
			if ( $refNumber === NULL && $descriptor === NULL ) {
				return $this->errDataSuplied ( 'No hay referencia numerica, descripcion o clave de rastreo' );
			}
			//validar que la transferencia sea a una cuenta clabe que pertenezca a una empresa registrada
			$clabeDestino = $input[ 'receiverBank' ] ?? NULL;
			if ( $clabeDestino === NULL ) {
				return $this->errDataSuplied ( 'Falta clabe de banco origen' );
			}
			$stp = new StpModel();
			$vClabe = $stp->validateClabe ( $clabeDestino, $this->env );
			if ( $vClabe[ 0 ] === FALSE ) {
				var_dump ( $vClabe );
				return $this->rollback ( $input, $vClabe[ 1 ], $this->env );
			}
			$op = new OperationModel ();
			$operation = $op->searchOperations ( $descriptor, $refNumber, $trackingKey, $this->env );
			if ( isset( $operation[ 0 ] ) ) {
				var_dump ( $operation );
				return $this->rollback ( $input, $operation[ 1 ], $this->env );
			}
			switch ( $operation[ 'origin' ] ) {
				case 'conciliacion':
					$do = $this->makeConciliation ( $operation, $input, $this->env );
					break;
				case 'conciliacionCPlus':
					$do = $this->makeConciliationPlus ( $operation, $input, $this->env );
					break;
				case 'dispercionPlus':
					$do = $this->makeDispertion ( $operation, $input, $this->env );
					break;
			}
			var_dump ( $operation );
			die();
			if ( !count ( $operation ) > 0 ) {
				return $this->getResponse ( $this->rollback ( $input, $this->env ) );
			}
			if ( $operation[ 'origin' ] === 'conciliacion' ) {
				$res = $this->doConciliation ( $operation, $this->env );
			} else if ( $operation[ 'origin' ] === 'conciliacionPlus' ) {
				$res = $this->doConciliationPlus ( $operation, $this->env );
			} else {
				$res = $this->doDispercionPlus ( $operation, $this->env );
			}
			return $this->getResponse ( $res );
			//			return $this->getResponse ( [ 'status' => 'correcto', "message" => "Información recibida y procesada correctamente" ] );
		}
		public function doConciliationPlus ( array $operation, string $env ) {
			$conc = new ConciliacionModel();
			
		}
		public function makeConciliation ( array $operation, array $input, string $env = NULL ) {
		}
		public function makeConciliationPlus ( array $operation, array $input, string $env ) {
		}
		public function makeDispertion ( mixed $operation, mixed $input, string $env ) {
		}
	}