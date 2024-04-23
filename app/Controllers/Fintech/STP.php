<?php
	
	namespace App\Controllers\Fintech;
	
	use CodeIgniter\HTTP\ResponseInterface;
	use App\Controllers\PagesStatusCode;
	use App\Models\StpModel;
	
	class STP extends PagesStatusCode {
		private string $env = 'SANDBOX';
		public function __construct () {
			parent::__construct ();
			helper ( 'tools' );
		}
		/**
		 * Decide el ambiente en el que trabajaran las funciones, por defecto SANDBOX
		 *
		 * @param mixed $env Variable con el ambiente a trabajar
		 *
		 * @return void Asigna el valor a la variable global
		 */
		public function environment ( mixed $env ): void {
			$this->env = isset($env['environment']) ? strtoupper ($env['environment']) : 'SANDBOX';
		}
		public function testCobro (): ResponseInterface {
			$stp = new StpModel();
			return $this->getResponse ( json_decode ( $stp->sendDispersion ( 'SANDBOX' ), TRUE ) );
		}
		public function testConsulta (): ResponseInterface {
			$stp = new StpModel();
			return $this->getResponse ( json_decode ( $stp->sendConsulta ( 'SANDBOX' ), TRUE ) );
		}
		/**
		 * @return ResponseInterface|bool
		 */
		public function webhook (): ResponseInterface|bool {
			if ( $data = $this->verifyRules ( 'JSON', 'POST', $this->request ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input);
			if (!createLog ( "incoming_stp_$this->env", json_encode ( $input ) )){
				return $this->getResponse ( [ 'error' => '500', 'description' => 'Proceso incompleto', 'reason' => 'No se logró guardar la información' ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR );
			}
			return $this->getResponse ( [ 'status' => 'correcto', "message" => "Información recibida y procesada correctamente" ] );
		}
		
	}