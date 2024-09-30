<?php
	
	namespace App\Controllers;
	
	use App\Models\SolveExpressModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class SolveExpress extends PagesStatusCode {
		public function payrollAdvanceReport (){
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 11 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$express = new SolveExpressModel();
		}
	}
