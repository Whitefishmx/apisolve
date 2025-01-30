<?php
	
	namespace App\Controllers;
	
	use App\Models\CfdiModel;
	use App\Models\DataModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class Data extends PagesStatusCode {
		/**
		 * Obtiene la información necesaria los CFDI para generar los pdf según se indique el UUID, categoría (1: CFDI de conciliación normal,
		 * 2:cfdi de conciliación plus, 3: notas de débito de CFDI plus),
		 * @return ResponseInterface|bool
		 */
		public function getCfdiInfo (): ResponseInterface|bool {
			if ( $data = $this->verifyRules ( 'GET', $this->request, NULL ) ) {
				return ( $data );
			}
			$input = $this->getGetRequestInput ( $this->request );
			$this->environment ( $input );
			$cfdi = new CfdiModel();
			$res = $cfdi->getCfdiPdf ( $input, $this->env );
			if ( count ( $res ) <= 0 ) {
				return $this->dataNotFound ();
			}
			return $this->getResponse ( $res );
		}
		/**
		 * Regresa información de codings postales según se envíe el code, ciudad o estado
		 * @return ResponseInterface|bool
		 */
		public function getCPInfo (): ResponseInterface|bool {
			if ( $data = $this->verifyRules ( 'GET', $this->request, NULL ) ) {
				return ( $data );
			}
			$input = $this->getGetRequestInput ( $this->request );
			if ( !isset( $input[ 'cp' ] ) && !isset( $input[ 'county' ] ) && !isset( $input[ 'state' ] ) ) {
				return $this->errDataSupplied ( 'Debe incluir al menos un elemento (C.P o Estado o Ciudad)' );
			}
			$this->environment ( $input );
			$dat = new DataModel();
			$res = $dat->getCPInfo ( $input, $this->env );
			if ( count ( $res ) <= 0 ) {
				return $this->dataNotFound ();
			}
			return $this->getResponse ( $res );
		}
		/**
		 * Obtiene el regimen según la clave
		 * @return ResponseInterface|bool
		 */
		public function getRegimen (): ResponseInterface|bool {
			if ( $data = $this->verifyRules ( 'GET', $this->request, NULL ) ) {
				return ( $data );
			}
			$input = $this->getGetRequestInput ( $this->request );
			$this->environment ( $input );
			$dat = new DataModel();
			$res = $dat->getRegimen ( $input[ 'clave' ], intval ( $input[ 'limit' ] ), $this->env );
			if ( count ( $res ) <= 0 ) {
				return $this->dataNotFound ();
			}
			return $this->getResponse ( $res );
		}
		public function getLaws (): ResponseInterface {
			$this->input = $this->getRequestLogin ( $this->request );
			if ( $this->verifyRules ( 'GET', $this->request, NULL ) ) {
				$this->logResponse ( 39 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$data = new DataModel();
			$law = $data->getLaws ( $this->input[ 'platform' ], $this->input[ 'type' ] );
			if ( !$law[ 0 ] ) {
				$this->errCode = 404;
				$this->dataNotFound ();
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Laws text found',
				'response'    => $law[ 1 ][ 'content' ],
			];
			//			$this->logResponse ( 39 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
	}