<?php
	
	namespace App\Controllers;
	
	use App\Models\DispersionModel;
	use CodeIgniter\HTTP\ResponseInterface;
	use Exception;
	
	class Dispersiones extends PagesStatusCode {
		private string $env = 'SANDBOX';
		/**
		 * Decide el ambiente en el que trabajaran las funciones, por defecto SANDBOX
		 *
		 * @param mixed $env Variable con el ambiente a trabajar
		 *
		 * @return void Asigna el valor a la variable global
		 */
		public function environment ( mixed $env ): void {
			$this->env = isset( $env[ 'environment' ] ) ? strtoupper ( $env[ 'environment' ] ) : 'SANDBOX';
		}
		/**
		 * Crea una dispersion masiva a partir de las conciliaciones creadas en Conciliation_plus
		 * @return ResponseInterface
		 * @throws Exception
		 */
		public function chosenForDispersion (): ResponseInterface {
			if ( $data = $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			$conciliations = $input[ 'conciliaciones' ];
			$user = json_decode ( base64_decode ( $input[ 'user' ] ), TRUE );
			$company = json_decode ( base64_decode ( $input[ 'company' ] ), TRUE );
			if ( isset( $conciliations ) || $conciliations === NULL ) {
				$conciliations = explode ( ',', $conciliations );
				$disp = new DispersionModel();
				$dispersions = $disp->createDispersionCP ( $conciliations, $user, $company, $this->env );
				if ( count ( $dispersions ) > 0 ) {
					return $this->getResponse ( [
						'error' => NULL, 'Message' => 'Dispersion creada correctamente' ] );
				}
				return $this->serverError ( 'No se logro crear la dispersion', 'No se selecciono ninguna conciliación' );
			}
			return $this->serverError ( 'No se logro crear la dispersion', 'No se selecciono ninguna conciliación' );
		}
	}