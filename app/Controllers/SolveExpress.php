<?php
	
	namespace App\Controllers;
	
	use App\Models\SolveExpressModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class SolveExpress extends PagesStatusCode {
		public function payrollAdvanceReport () {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 11 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$validation = service ( 'validation' );
			$validation->setRules (
				[
					'user'     => 'permit_empty|max_length[7]|numeric',
					'employee' => 'permit_empty|max_length[7]',
					'company'  => 'permit_empty|max_length[7]|numeric',
					'initDate' => 'permit_empty|regex_match[\d{4}-\d{2}-\d{2}]',
					'endDate'  => 'permit_empty|regex_match[\d{4}-\d{2}-\d{2}]',
					'plan'     => 'permit_empty|max_length[1]|alpha',
					'rfc'      => 'permit_empty|max_length[18]|regex_match[^[A-ZÑ&]{3,4}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[A-Z\d]{2}[A\d]$]',
					'name'     => 'permit_empty|alpha_space|max_length[150]|',
				],
				[ 'user' => [ 'max_length' => 'El id de usuario no debe tener mas de {param} caracteres' ] ] );
			if ( !$validation->run ( $this->input ) ) {
				$errors = $validation->getErrors ();
				$this->errDataSupplied ( $errors );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$express = new SolveExpressModel();
			$res = $express->getReport ( $this->input );
			if (!$res[0]){
				$this->errCode=404;
				$this->dataNotFound ();
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Reporte generado correctamente',
				'response'    => $res[1],
			];
			return $this->getResponse ( $this->responseBody, $this->errCode);
		}
	}
