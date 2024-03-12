<?php
	
	namespace App\Validation;
	
	use App\Models\UserModel;
	
	class UserRules {
		function cifrarAES ( $mensaje ) {
			$clave = 'R$#9pL@z&*Q!7k#J2';
			$iv = hex2bin ( '845e409135219b45a30ca1c60a7e0c77' );
			$cifrado = openssl_encrypt ( $mensaje, 'AES-256-CBC', $clave, 0, $iv );
			return base64_encode ( $cifrado );
		}
		public function validateUser ( string $str, string $fields, array $data ): bool {
			try {
				$model = new UserModel;
				$user = $model->authenticateToken ( $data[ 'usuario' ] );
				return $user[ 'password' ] === $this->cifrarAES ( $data[ 'contraseña' ] );
			} catch ( \Exception $e ) {
				return FALSE;
			}
		}
	}
