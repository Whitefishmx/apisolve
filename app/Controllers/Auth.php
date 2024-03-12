<?php
	
	namespace App\Controllers;
	
	use App\Controllers\BaseController;
	use App\Models\UserModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class Auth extends BaseController {
		/**
		 * Método para poder autenticarse y obtener un nuevo Token
		 * @return ResponseInterface responde error o el nuevo token
		 */
		public function login (): ResponseInterface {
			$rules = [
				'usuario' => 'required|min_length[4]|max_length[50]',
				'contraseña' => 'required|min_length[8]|max_length[255]|validateUser[usuario, contraseña]',
			];
			$errors = [
				'contraseña' => [
					'validateUser' => 'Datos de inicio de sesión incorrectos',
				],
			];
			$input = $this->getRequestInput ( $this->request );
			if ( !$this->validateRequest ( $input, $rules, $errors ) ) {
				return $this->getResponse ( $this->validator->getErrors (), ResponseInterface::HTTP_BAD_REQUEST );
			}
			return $this->getJWTForUser ( $input[ 'usuario' ], $input[ 'contraseña' ] );
		}
		/**
		 * Método para obtener un nuevo token
		 *
		 * @param string $user     Usuario al que se le generara el token
		 * @param string $password Contraseña del usuario
		 *
		 * @return ResponseInterface
		 */
		private function getJWTForUser ( string $user, string $password ): ResponseInterface {
			try {
				$model = new UserModel();
				$user = $model->authenticateToken ( $user, $password, 'SANDBOX' );
				helper ( 'jwt' );
				$jwt = getSignedJWTForUser ( $user[ 'email' ] );
				return $this->getResponse ( [
					'message' => 'Usuario autenticado satisfactoriamente',
					'access_token' => [ $jwt ],
				], ResponseInterface::HTTP_OK);
			} catch ( \Exception $e ) {
				return $this->getResponse ( [
					'error' => $e->getMessage (),
				], ResponseInterface::HTTP_UNAUTHORIZED );
			}
		}
	}
