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
		}
		public function testCobro (): ResponseInterface {
			if ( $data = $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			helper ( 'tools_helper' );
			helper ( 'tetraoctal_helper' );
			$input[ 'folio' ] = serialize32 ( [ rand ( 1, 9 ), rand ( 1, 9 ), rand ( 1, 9 ), rand ( 1, 9 ), rand ( 1, 31221 ) ] );
			$input[ 'concepto' ] = 'Prueba ' . rand ( 0, 255 );
			$input[ 'refNumeric' ] = MakeOperationNumber ( rand ( 1, 250 ) );
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
		/**
		 * Webhook para obtener la entrada de recursos en la cuenta de STP
		 * @return ResponseInterface|bool
		 */
		public function wbAbonos (): ResponseInterface|bool {
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
				$this->rollback ( $input, $this->env );
			}
			$op = new OperationModel ();
			$operation = $op->searchOperations ( $descriptor, $refNumber, $trackingKey, $this->env );
			if ( $operation[ 0 ] === FALSE ) {
				$this->rollback ( $input, $this->env );
			}
			switch ( $operation[ 'origin' ] ) {
				case 'conciliacion':
					$do= $this->makeConciliation($operation, $input, $this->env);
					break;
				case 'conciliacionCPlus':
					$do= $this->makeConciliationPlus($operation, $input, $this->env);
					break;
				case 'dispercionPlus':
					$do= $this->makeDispertion($operation, $input, $this->env);
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
		public function rollback ( mixed $input, string $env ) {
			$args = [
				'operationNumber' => $input[ 'reference_number' ],
				'trakingKey' => $input[ 'tracking_key' ],
				'opId' => $input[ 'speid_id' ],
				'amount' => $input[ 'amount' ],
				'descriptor' => $input[ 'descriptor' ],
				'sourceBank' => $input[ 'sourceBank' ],
				'receiverBank' => $input[ 'receiverBank' ],
				'transactionDate' => $input[ 'transactionDate' ],
				'sourceRfc' => $input[ 'sourceRfc' ] ?? NULL,
				'receiverRfc' => $input[ 'receiverRfc' ] ?? NULL,
			];
			$this->AddMovement ( $args, $env );
			return $input;
		}
		public function AddMovement ( array $args, string $env = NULL ) {
			$op = new OperationModel ();
			$res = $op->AddMovement ( $args, $env );
			var_dump ( $res );
			return $this->serverError ( $res[ 1 ], $res[ 1 ] );
			$binnacle [ 'L' ] = [ 'id_c' => 1, 'id' => 1, 'module' => 3, 'code' => $res[ 'code' ],
				'in' => json_encode ( $args ),
				'out' => json_encode ( $res ) ];
			$this->Binnacle ( $binnacle, 0, [ 3 ], 3, $this->environment );
		}
		public function makeConciliation ( array $operation, array $input, string $env = NULL ) {
		}
		public function makeConciliationPlus ( array $operation, array $input, string $env ) {
		}
		public function makeDispertion ( mixed $operation, mixed $input, string $env ) {
		}
	}