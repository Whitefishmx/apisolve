<?php
	
	namespace App\Controllers;
	
	use Exception;
	use App\Models\UserModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class Users extends PagesStatusCode {
		/**
		 * @throws Exception
		 */
		public function setUser (): ResponseInterface|bool|array {
			$this->input = $this->getRequestLogin ( $this->request );
			if ( $data = $this->verifyRules ( 'PATCH', $this->request, 'JSON' ) ) {
				$this->logResponse ( 31 );
				return ( $data );
			}
			$rules = [
				'user'        => 'required|max_length[7]',
				'nickName'    => 'required|max_length[10]|is_unique[users.nickname]',
				'email'       => 'required|valid_email',
				'phone'       => 'permit_empty|exact_length[10]|numeric',
				'contraseña'  => 'required|min_length[8]|max_length[100]',
				'contraseña2' => 'required|min_length[8]|max_length[255]|matches[contraseña]',
			];
			$errors = [
				'user'        => [
					'required'   => 'El campo {field} es obligatorio',
					'max_length' => 'El campo {field} no puede ser mayo a {param} caracteres' ],
				'nickName'    => [
					'required'   => 'El campo {field} es obligatorio',
					'max_length' => 'El campo {field} no puede ser mayo a {param} caracteres',
					'is_unique'  => 'Ya existe un usuario con el mismo alias, pruebe con otro' ],
				'email'       => [
					'required'    => 'El campo {field} es obligatorio',
					'valid_email' => 'Por favor introduzca un correo valido', ]
				,
				'phone'       => [
					'exact_length' => 'Por favor introduzca un numero telefónico valido de 10 dígitos',
					'numeric'      => 'El formato no es valido' ],
				'contraseña'  => [
					'required'   => 'El campo contraseña es obligatorio',
					'min_length' => 'La contraseña no debe ser menor a {param} caracteres',
					'max_length' => 'La contraseña no debe ser mayor a {param} caracteres' ],
				'contraseña2' => [
					'required'   => 'El campo contraseña es obligatorio',
					'min_length' => 'La contraseña no debe ser menor a {param} caracteres',
					'max_length' => 'La contraseña no debe ser mayor a {param} caracteres',
					'matches'    => 'Las contraseñas no coinciden' ],
			];
			$validated = $this->validateArgsRules ( $rules, $errors );
			if ( !$validated ) {
				$this->logResponse ( 31 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			helper ( 'crypt_helper' );
			$user = new UserModel ();
			$phone = $this->input[ 'phone' ] ?? NULL;
			$res = $user->updateProfile ( $this->input[ 'nickName' ], $this->input[ 'email' ], passwordEncrypt ( $this->input[ 'contraseña' ] ),
				$phone, $this->input[ 'user' ] );
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