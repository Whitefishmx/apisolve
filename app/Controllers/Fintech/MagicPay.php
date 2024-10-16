<?php
	
	namespace App\Controllers\Fintech;
	
	use App\Models\MagicPayModel;
	use App\Controllers\PagesStatusCode;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class MagicPay extends PagesStatusCode {
		public function MagicWH (): ResponseInterface|bool|array {
			$this->user = 14;
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 3, $this->input, $this->responseBody );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->errCode = 200;
			$this->input = $this->getRequestLogin ( $this->request );
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
		public function getBalance (): ResponseInterface {
			if ( $this->verifyRules ( 'GET', $this->request, NULL ) ) {
				$this->logResponse ( 5 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			if ( !$this->validateSession () ) {
				$this->redirectLogIn ();
			}
			$magic = new MagicPayModel();
			$res = $magic->getBalance ();
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error al obtener el balance', 'No se pudo obtener el balance' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Datos correctos',
				'response'    => json_decode ( $res[ 'response' ], TRUE )[ 'result' ] ];
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function getTransfers (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 6 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			if ( !$this->validateSession () ) {
				$this->redirectLogIn ();
			}
			$magic = new MagicPayModel();
			$data = $this->input !== NULL ? $this->input : [];
			$res = $magic->getTransfers ( $data );
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error al obtener el balance', 'No se pudo obtener el balance' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Datos correctos',
				'response'    => json_decode ( $res[ 'response' ], TRUE )[ 'result' ] ];
			$this->logResponse ( 6 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function getTransfersById (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 6 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			if ( !$this->validateSession () ) {
				$this->redirectLogIn ();
			}
			$validation = service ( 'validation' );
			$validation->setRules ( [
				'id' => 'required|max_length[30]|alpha_numeric',
			], [
				'id' => [
					'max_length'    => 'El campo {field} no debe tener mas de {param} caracteres',
					'required'      => 'El campo {field} no debe estar vacío',
					'alpha_numeric' => 'No es un formato valido para el ID de transferencia' ],
			] );
			if ( !$validation->run ( $this->input ) ) {
				$this->errDataSupplied ( $validation->getErrors () );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$magic = new MagicPayModel();
			$res = $magic->getTransfersByID ( $this->input[ 'id' ] );
			//			die(var_dump ($res));
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error al obtener el balance', 'No se pudo obtener el balance' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Datos correctos',
				'response'    => json_decode ( $res[ 'response' ], TRUE )[ 'result' ] ];
			$this->logResponse ( 8 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function transfer (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 6 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			if ( !$this->validateSession () ) {
				$this->redirectLogIn ();
			}
			$validation = service ( 'validation' );
			$validation->setRules ( [
				'account'     => 'required|max_length[18]|numeric|min_length[16]',
				'amount'      => 'required|max_length[18]|regex_match[^(0|[1-9]\d*)(\.\d{1,2})?$|^0?\.\d{1,2}$]',
				'description' => 'max_length[18]',
				'owner'       => 'required|max_length[30]',
			], [
				'account' => [
					'max_length' => 'La clabe bancaria no debe tener mas de {param} caracteres',
					'required'   => 'La clabe bancaria no debe estar vacío',
					'numeric'    => 'Formato invalido para la clabe bancaria',
					'min_length' => 'Formato invalido para la clabe bancaria' ],
			] );
			if ( !$validation->run ( $this->input ) ) {
				$this->errDataSupplied ( $validation->getErrors () );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$magic = new MagicPayModel();
			$res = $magic->createTransfer ( $this->input );
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error al crear la transferencia', 'No se pudo realizar la transacción' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Datos correctos',
				'response'    => json_decode ( $res[ 'response' ], TRUE )[ 'result' ] ];
			$this->logResponse ( 8 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
	}