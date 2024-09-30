<?php
	
	namespace App\Controllers;
	
	use App\Models\UserModel;
	use CodeIgniter\Config\Services;
	use CodeIgniter\HTTP\ResponseInterface;
	use Exception;
	
	class Auth extends PagesStatusCode {
		/**
		 * Método para poder autenticarse y obtener un nuevo Token
		 * @return ResponseInterface|array responde error o el nuevo token
		 */
		public function login (): ResponseInterface|array {
			if ( $data = $this->verifyRules ( 'GET', $this->request, NULL ) ) {
				return ( $data );
			}
			$input = $this->getGetRequestInput ( $this->request );
			$this->environment ( $input );
			$rules = [
				'usuario'    => 'required|min_length[4]|max_length[50]',
				'contraseña' => 'required|min_length[8]|max_length[255]|validateUser[usuario, contraseña, environment]',
			];
			$errors = [
				'contraseña' => [
					'validateUser' => 'Datos de inicio de sesión incorrectos',
				],
			];
			if ( !$this->validateRequest ( $input, $rules, $errors ) ) {
				$this->errDataSupplied ( 'Usuario y/o contraseña incorrectos.' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			return ( $this->getJWTForUser ( $input[ 'usuario' ] ) );
		}
		/**
		 * Método para obtener un nuevo token
		 *
		 * @param string $user Usuario al que se le generara el token
		 *
		 * @return ResponseInterface
		 */
		private function getJWTForUser ( string $user ): ResponseInterface {
			try {
				$model = new UserModel();
				$user = $model->authenticateToken ( $user );
				helper ( 'jwt' );
				$jwt = getSignedJWTForUser ( $user[ 'email' ], $user[ 'id' ] );
				return $this->getResponse ( [
					'message'      => 'Usuario autenticado satisfactoriamente',
					'access_token' => $jwt ] );
			} catch ( Exception $e ) {
				return $this->getResponse ( [
					'error' => $e->getMessage (),
				], ResponseInterface::HTTP_UNAUTHORIZED );
			}
		}
		public function signIn (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				$this->logResponse ( 1 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$user = new UserModel();
			helper ( 'crypt_helper' );
			$res = $user->validateAccess ( $this->input[ 'email' ], $this->input[ 'password' ] = ( passwordEncrypt (
				$this->input[ 'password' ] ) ), intval ( $this->input[ 'platform' ] ) );
			if ( !$res[ 0 ] ) {
				$this->errDataSupplied ( 'Las credenciales ingresadas son incorrectas' );
				//				$this->logResponse ( 1 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$session = session ();
			$session->set ( 'logged_in', TRUE );
			$session->set ( 'user', $res[ 1 ][ 'id' ] );
			helper ( 'jwt' );
			$jwt = getSignedJWTForUser ( $this->input[ 'email' ], $res[ 1 ][ 'userData' ][ 'id' ] );
			$this->errCode = 200;
			$this->responseBody = [
				'error'        => 0,
				'user'         => [
					'permissions' => $res[ 1 ][ 'permissions' ],
					'data'        => $res[ 1 ][ 'userData' ] ],
				'access_token' => $jwt,
				'logged_in'    => TRUE ];
			//			$this->logResponse ( 1 );
			return $this->getResponse ( $this->responseBody );
		}
		public function tokenAlive (): ResponseInterface {
			$authenticationHeader = $this->request->getServer ( 'HTTP_AUTHORIZATION' );
			try {
				helper ( 'jwt' );
				$encodedToken = getJWTFromRequest ( $authenticationHeader );
				$jwt = validateJWTFromRequest ( $encodedToken );
				if ( $jwt[ 0 ] === FALSE ) {
					$this->serverError ( 'Error con el token', $jwt[ 1 ] );
					return $this->getResponse ( $this->responseBody, $this->errCode );
				}
				$newJwt = getSignedJWTForUser ( $jwt[1][ 'email' ], $jwt[1][ 'id' ] );
				$this->responseBody = [
					'error'        => 0,
					'user'         => [
						'data'        => $jwt[1] ],
					'access_token' => $newJwt,
					'logged_in'    => TRUE ];
				return $this->getResponse ( $this->responseBody, $this->errCode );
			} catch ( \Exception $e ) {
				return $this->getResponse ( [ 'error' => $e->getMessage (), ], 401 );
			}
		}
	}
