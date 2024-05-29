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
				return $this->errDataSuplied ( 'Debe incluir al menos un elemento (C.P o Estado o Ciudad)' );
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
	}