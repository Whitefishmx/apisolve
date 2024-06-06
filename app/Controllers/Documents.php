<?php
	
	namespace App\Controllers;
	
	use App\Models\CfdiModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class Documents extends PagesStatusCode {
		public function getCFDI ():ResponseInterface|bool {
			/** @noinspection DuplicatedCode */
			if ( $in = $this->verifyRules ( 'GET', $this->request, NULL ) ) {
				return ( $in );
			}
			$input = $this->getGetRequestInput ( $this->request );
			$this->environment ( $input );
			$company = $input[ 'company' ] ?? NULL;
			$uuid = $input[ 'uuid' ];
			$args = $input[ 'args' ];
			if ( $input[ 'sencilla' ] > 0 && $input[ 'plus' ] > 0 ) {
				$show = 3;
			} else if ( $input[ 'sencilla' ] > 0 && $input[ 'plus' ] < 1 ) {
				$show = 1;
			} else if ( $input[ 'plus' ] > 0 && $input[ 'sencilla' ] < 1 ) {
				$show = 2;
			} else {
				$show = 0;
			}
			[ $from, $to ] = $this->dateFilter ( $input, 'from', 'to' );
			[ $fromUpdate, $toUpdate ] = $this->dateFilter ( $input, 'from2', 'to2' );
			$cfdi = new CfdiModel();
			$res = $cfdi->getDocsCfdi ( $from, $to, $fromUpdate, $toUpdate, $uuid, $args, $show, $company, $this->env );
			return $this->getResponse ( $res );
		}
	}