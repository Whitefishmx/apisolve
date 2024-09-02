<?php
	
	namespace App\Controllers\Fintech;
	
	use App\Controllers\PagesStatusCode;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class MagicPay extends PagesStatusCode {
		public function MagicWH (): ResponseInterface|bool|array {
			$this->user = 14;
			if ( $data = $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 3, $this->input, $this->responseBody );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->errCode = 200;
			$this->input = $this->getRequestInput ( $this->request );
			$out = [
				'error'       => $this->errCode,
				'description' => 'Información recibida.',
				'reason'      => 'Los datos se recibieron y procesaron con éxito.' ];
			if ( !$this->logResponse ( 3, $this->input, $out ) ) {
				$this->serverError ( 'Proceso incompleto', 'No se logró guardar la información' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = $out;
			return $this->getResponse ( $this->responseBody );
		}
	}