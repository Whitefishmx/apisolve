<?php
	
	namespace App\Controllers;
	
	use App\Controllers\BaseController;
	use App\Models\UserModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class Users extends PagesStatusCode {
		/**
		 * @throws \Exception
		 */
		public function changePassword () {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $data = $this->verifyRules ( 'PATCH', $this->request, NULL ) ) {
				$this->logResponse ( 31 );
				return ( $data );
			}
			$rules = [
				'contraseña'  => 'required|min_length[8]|max_length[255]',
				'contraseña2' => 'required|min_length[8]|max_length[255]|matches[contraseña]',
			];
			$errors = [
				'contraseña'  => [ 'required' => 'el campo contraseña es obligatorio' ],
				'contraseña2' => [],
			];
			$validated = $this->validateArgsRules ( $rules, $errors );
			if ( !$validated ) {
				$this->logResponse ( 31 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			helper ( 'crypt_helper' );
			$user = new UserModel ();
			$res = $user->updatePassword ( $this->user, passwordEncrypt ( $this->input[ 'contraseña' ] ) );
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error en la transaccion', $res[ 1 ] );
				$this->logResponse ( 31 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->errCode = 200;
			$this->responseBody = [
				'error'       => 200,
				'description' => 'Contraseña actualizada exitosamente',
				'response'    => 'ok' ];
			$this->logResponse ( 31 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
	}