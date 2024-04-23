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
			$stp = new StpModel();
			return $this->getResponse ( json_decode ( $stp->sendDispersion ( 'SANDBOX' ), TRUE ) );
		}
		public function testConsulta (): ResponseInterface {
			$stp = new StpModel();
			return $this->getResponse ( json_decode ( $stp->sendConsulta ( 'SANDBOX' ), TRUE ) );
		}
		public function webhook (): ResponseInterface|bool {
			if ( $data = $this->verifyRules ( 'JSON', 'POST', $this->request ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			if (!createLog ( 'incoming_stp', json_encode ( $input ) )){
				return $this->getResponse ( [ 'error' => '500', 'description' => 'Proceso incompleto', 'reason' => 'No se logró guardar la información' ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR );
			}
			return $this->getResponse ( [ 'status' => 'correcto', "message" => "Información recibida y procesada correctamente" ] );
		}
	}