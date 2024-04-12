<?php
	
	namespace App\Controllers;
	
	use App\Models\DispersionModel;
	use CodeIgniter\HTTP\ResponseInterface;
	use Exception;
	
	class Dispersiones extends BaseController {
		private string $environment = 'SANDBOX';
		/**
		 * Crea una dispersion masiva a partir de las conciliaciones creadas en Conciliation_plus
		 * @return ResponseInterface
		 * @throws Exception
		 */
		public function chosenForDispersion (): ResponseInterface {
			$input = $this->getRequestInput ( $this->request );
			$conciliations = $input[ 'conciliaciones' ];
			$user = json_decode ( base64_decode ( $input[ 'user' ] ), TRUE );
			$company = json_decode ( base64_decode ( $input[ 'company' ] ), TRUE );
			if ( isset( $conciliations ) || $conciliations === NULL ) {
				$conciliations = explode ( ',', $conciliations );
				$disp = new DispersionModel();
				$dispersions = $disp->createDispersionCP ( $conciliations, $user, $company, $this->environment );
				if (count ($dispersions)>0){
					return $this->getResponse ( [
						'error' => 'Ok', 'Message' => 'Dispersion creada correctamente' ]);
				}
				return $this->getResponse ( [
					'error' => 'No se logro crear la dispersion', 'reason' => 'No se selecciono ninguna conciliación' ],
					ResponseInterface::HTTP_INTERNAL_SERVER_ERROR );
			}
			return $this->getResponse ( [
				'error' => 'No se logro crear la dispersion', 'reason' => 'No se selecciono ninguna conciliación' ],
				ResponseInterface::HTTP_INTERNAL_SERVER_ERROR );
		}
	}