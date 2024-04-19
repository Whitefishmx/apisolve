<?php
	
	namespace App\Controllers\Fintech;
	
	use CodeIgniter\HTTP\ResponseInterface;
	use App\Controllers\BaseController;
	use App\Models\StpModel;
	
	class STP extends BaseController {
		public function __construct () {
			parent::__construct ();
			helper ( 'tools' );
		}
		public function testCobro (): ResponseInterface {
			$stp = new StpModel();
			return $this->getResponse ( json_decode ( $stp->sendDispersion ( 'SANDBOX' ), TRUE ) );
		}
		public function testConsulta () {
			$stp = new StpModel();
			return $this->getResponse ( json_decode ( $stp->sendConsulta ( 'SANDBOX' ), TRUE ) );
		}
		public function webhook (): ResponseInterface {
			$input = $this->getRequestInput ( $this->request );
			createLog ( 'incoming_stp', json_encode ( $input ) );
			return $this->getResponse ( [ 'res' => 'ok' ] );
		}
	}