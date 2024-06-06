<?php
	
	namespace App\Controllers;
	
	use App\Models\DispersionModel;
	use CodeIgniter\HTTP\ResponseInterface;
	use Exception;
	
	class Dispersiones extends PagesStatusCode {
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
				if ( !$dispersions[ 0 ] ) {
					return $this->serverError ( 'Error proceso incompleto', $dispersions[ 1 ] );
				}
				if ( count ( $dispersions ) > 0 ) {
					return $this->getResponse ( [
						'error' => NULL, 'Message' => 'Dispersion creada correctamente' ] );
				}
				return $this->serverError ( 'No se logro crear la dispersion', 'No se selecciono ninguna conciliación' );
			}
			return $this->serverError ( 'No se logro crear la dispersion', 'No se selecciono ninguna conciliación' );
		}
		/**Obtener un listado de dispersiones masivas por empresa
		 * @return ResponseInterface|bool
		 */
		public function getDispersionPlus (): ResponseInterface|bool {
			if ( $data = $this->verifyRules ( 'GET', $this->request, NULL ) ) {
				return ( $data );
			}
			$input = $this->getGetRequestInput ( $this->request );
			$this->environment ( $input );
			$company = $input[ 'company' ] ?? NULL;
			$folio = $input[ 'folio' ] ?? NULL;
			$numeric = intval ( $input[ 'numeric' ] ) ?? NULL;
			[ $from, $to ] = $this->dateFilter ( $input, 'from', 'to' );
			if ( $company === NULL ) {
				return $this->serverError ( 'Recurso no encontrada', 'Se esperaba el ID de la compañía a buscar' );
			}
			$dispersion = new DispersionModel();
			$res = $dispersion->getDispersionesPlus ( $folio, $numeric, $from, $to, $company, $this->env );
			if ( count ( $res ) <= 0 ) {
				return $this->dataNotFound ();
			}
			if ( !$res[ 0 ] ) {
				return $this->serverError ( 'Error proceso incompleto', $res[ 1 ] );
			}
			return $this->getResponse ( $res );
		}
	}