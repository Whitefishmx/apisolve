<?php
	
	namespace App\Controllers;
	
	use Exception;
	use App\Models\UserModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class Users extends PagesStatusCode {
		protected string|UserModel $userData = '';
		public function __construct () {
			parent::__construct ();
			helper ( 'crypt_helper' );
			$this->userData = new UserModel ();
		}
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
			$phone = $this->input[ 'phone' ] ?? NULL;
			$res = $this->userData->updateProfile ( $this->input[ 'nickName' ], $this->input[ 'email' ], passwordEncrypt ( $this->input[ 'contraseña' ] ),
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
		public function resetPassword (): ResponseInterface|bool|array {
			$this->input = $this->getRequestLogin ( $this->request );
			if ( $data = $this->verifyRules ( 'PATCH', $this->request, 'JSON' ) ) {
				$this->logResponse ( 58 );
				return ( $data );
			}
			$rules = [
				'user'      => 'required|max_length[7]|numeric',
				'code'      => 'required|max_length[50]|regex_match[([\dA-Z]){32}]',
				'password'  => 'required|min_length[8]|max_length[100]',
				'password2' => 'required|min_length[8]|max_length[255]|matches[password]',
			];
			$errors = [
				'user'      => [
					'required'   => 'El campo {field} es obligatorio',
					'max_length' => 'El campo {field} no puede ser mayo a {param} caracteres' ],
				'password'  => [
					'required'   => 'El campo contraseña es obligatorio',
					'min_length' => 'La contraseña no debe ser menor a {param} caracteres',
					'max_length' => 'La contraseña no debe ser mayor a {param} caracteres' ],
				'password2' => [
					'required'   => 'El campo contraseña es obligatorio',
					'min_length' => 'La contraseña no debe ser menor a {param} caracteres',
					'max_length' => 'La contraseña no debe ser mayor a {param} caracteres',
					'matches'    => 'Las contraseñas no coinciden' ],
			];
			$validated = $this->validateArgsRules ( $rules, $errors );
			if ( !$validated ) {
				$this->logResponse ( 58 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$res = $this->userData->resetPassword ( $this->input[ 'user' ], passwordEncrypt ( $this->input[ 'code' ] ), passwordEncrypt ( $this->input[ 'password' ] ) );
			if ( !$res ) {
				$this->serverError ( 'Error en la transaccion', 'No se logro cambiar la contraseña intente nuevamente' );
				$this->logResponse ( 59 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->errCode = 200;
			$this->responseBody = [
				'error'       => 200,
				'description' => 'Contraseña actualizada exitosamente',
				'response'    => 'La contraseña se actualizo de forma exitosa, intente iniciar sesión nuevamente' ];
			$this->logResponse ( 59 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function requestReset (): ResponseInterface {
			$email = 'uriel.magallon@whitefish.mx';
			//			$email = 'alejandro@whitefish.mx';
			$emailController = new Email();
			$emailResponse = $emailController->sendPasswordResetEmail ( $email );
			return $this->response->setJSON ( $emailResponse );
		}
	}