<?php
	
	namespace App\Validation;
	
	use App\Models\UserModel;
	
	class UserRules {
		function cifrarAES ( $id ) {
			$clave = 'D0170rN07T#3r31sN07Tr&';
			$datos = $id;
			$metodo = 'aes-256-cbc';
			$iv = hex2bin('34857d973953e44afb49ea9d61104d8c');
			
			return openssl_encrypt($datos, $metodo, $clave, 0, $iv);
		}
		public function validateUser ( string $str, string $fields, array $data, string $env ): bool {
			try {
				$model = new UserModel;
				$user = $model->authenticateToken ( $data[ 'usuario' ], $env );
				return $user[ 'password' ] === $this->cifrarAES ( $data[ 'contraseña' ] );
			} catch ( \Exception $e ) {
				return FALSE;
			}
		}
	}
