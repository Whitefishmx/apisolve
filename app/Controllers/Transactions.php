<?php
	
	namespace App\Controllers;
	
	use App\Models\TransactionsModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class Transactions extends PagesStatusCode {
		public function downloadCep (): ResponseInterface {
			if ( $this->verifyRules ( 'GET', $this->request, NULL ) ) {
				$this->logResponse ( 27 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$transaction = new TransactionsModel();
			$data = $transaction->getDataForCep ();
			if ( !$data[ 0 ] ) {
				$this->dataNotFound ();
				$this->logResponse ( 27 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$res = [];
			//			var_dump ($data );
			//			die();
			foreach ( $data[ 1 ] as $value ) {
				$download = $transaction->DownloadCEP ( $value, 0 );
				if ( $download > 0 ) {
					$folio = str_replace ( "SSOLVE", "", $value[ 'external_id' ] );
					$res[] = [ 'idTransaction' => $value[ 'id' ], 'folio' => $folio, 'filename' => $download ];
				}
			}
			foreach ( $res as $key ) {
				$transaction->insertCep ( $key );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Proceso ejecutado',
				'response'    => 'Finalizo el proceso de verificaciones de CEP',
			];
			//			$this->logResponse ( 11 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
	}
