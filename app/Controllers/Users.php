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
			if ( $data = $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				return ( $data );
			}
			$rules = [
				'contraseña'  => 'required|min_length[8]|max_length[255]',
				'contraseña2' => 'required|min_length[8]|max_length[255]|differs[contraseña]',
			];
			$errors = [
				'contraseña' =>['required'=> 'el campo contraseña es obligatorio'],
				'contraseña2' => [
                    'differs' => 'Las contraseñas no coinciden',
                ],
			];
			$validated = $this->validateArgsRules ($rules, $errors );
			return $this->getResponse ( $this->responseBody, $this->errCode );
			var_dump ( $validated );
			die();
			if ( !$validated) {
				$this->errDataSupplied ( 'Usuario y/o contraseña incorrectos.' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
	}