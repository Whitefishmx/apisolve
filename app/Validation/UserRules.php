<?php
	
	namespace App\Validation;
	
	use App\Models\UserModel;
	
	class UserRules {
		function cifrarAES ( $id ): bool|string {
			$clave = 'D0170rN07T#3r31sN07Tr&';
			$datos = $id;
			$metodo = 'aes-256-cbc';
			$iv = hex2bin ( '34857d973953e44afb49ea9d61104d8c' );
			return openssl_encrypt ( $datos, $metodo, $clave, 0, $iv );
		}
		public function validateUser ( string $str, string $fields, array $data ): bool {
			try {
				$model = new UserModel;
				$user = $model->authenticateToken ( $data[ 'usuario' ], $data[ 'environment' ] );
				helper ( 'crypt_helper' );
				return $user[ 'password' ] === passwordEncrypt ( $data[ 'contraseña' ] );
			} catch ( \Exception $e ) {
				return FALSE;
			}
		}
	}
