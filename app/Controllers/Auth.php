<?php
	
	namespace App\Controllers;
	
	use App\Models\UserModel;
	use CodeIgniter\HTTP\ResponseInterface;
	use Exception;
	
	class Auth extends PagesStatusCode {
		private string $env = 'SANDBOX';
		/**
		 * Decide el ambiente en el que trabajaran las funciones, por defecto SANDBOX
		 *
		 * @param mixed $env Variable con el ambiente a trabajar
		 *
		 * @return void Asigna el valor a la variable global
		 */
		public function environment ( mixed $env ): void {
			$this->env = isset( $env[ 'environment' ] ) ? strtoupper ( $env[ 'environment' ] ) : 'SANDBOX';
		}
		/**
		 * Método para poder autenticarse y obtener un nuevo Token
		 * @return ResponseInterface responde error o el nuevo token
		 */
		public function login (): ResponseInterface {
			if ( $data = $this->verifyRules (  'GET', $this->request,'JSON' ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			$rules = [
				'usuario' => 'required|min_length[4]|max_length[50]',
				'environment' => 'required|max_length[8]',
				'contraseña' => 'required|min_length[8]|max_length[255]|validateUser[usuario, contraseña, environment]',
			];
			$errors = [
				'contraseña' => [
					'validateUser' => 'Datos de inicio de sesión incorrectos',
				],
			];
			if ( !$this->validateRequest ( $input, $rules, $errors ) ) {
				return $this->getResponse ( [ 'error' => 400, 'description' => 'Inicio de sesión incorrecto', 'reason' => 'Usuario y/o contraseña incorrectos.' ],
					ResponseInterface::HTTP_BAD_REQUEST );
			}
			return $this->getJWTForUser ( $input[ 'usuario' ], $this->env );
		}
		/**
		 * Método para obtener un nuevo token
		 *
		 * @param string $user Usuario al que se le generara el token
		 * @param        $env
		 *
		 * @return ResponseInterface
		 */
		private function getJWTForUser ( string $user, $env ): ResponseInterface {
			try {
				$model = new UserModel();
				$user = $model->authenticateToken ( $user, $env );
				helper ( 'jwt' );
				$jwt = getSignedJWTForUser ( $user[ 'email' ] );
				return $this->getResponse ( [
					'message' => 'Usuario autenticado satisfactoriamente',
					'access_token' => [ $jwt ] ] );
			} catch ( Exception $e ) {
				return $this->getResponse ( [
					'error' => $e->getMessage (),
				], ResponseInterface::HTTP_UNAUTHORIZED );
			}
		}
	}
