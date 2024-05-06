<?php
	
	namespace App\Controllers\Fintech;
	
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
			$input[ 'folio' ] = serialize32 ([ rand (1,9), rand (1,9), rand (1,9), rand (1,9), rand (1,31221) ]);
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
			if ( !createLog ( "wbAbonos_stp_$this->env", json_encode ( $input ) ) ) {
				return $this->serverError ( 'Proceso incompleto', 'No se logró guardar la información' );
			}
			return $this->getResponse ( [ 'status' => 'correcto', "message" => "Información recibida y procesada correctamente" ] );
		}
	}