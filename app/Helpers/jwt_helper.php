<?php
	
	use Config\Services;
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	use App\Models\UserModel;
	
	/**
	 * @param $authenticationHeader
	 *
	 * @return string
	 */
	function getJWTFromRequest ( $authenticationHeader ): string {
		if ( is_null ( $authenticationHeader ) ) {
			return FALSE;
		}
		return explode ( ' ', $authenticationHeader )[ 1 ];
	}
	
	/**
	 * @param string $encodedToken
	 *
	 * @return array
	 */
	function validateJWTFromRequest ( string $encodedToken ): array {
		$key = Services::getSecretKey ();
		try {
			$decodedToken = JWT::decode ( $encodedToken, new Key( $key, 'HS256' ) );
		}catch ( Exception $e ) {
			echo json_encode ( [ 'error' => 303 , 'description' => "Error de autenticación", 'reason' => "Token invalido o expirado" ] );
			http_response_code ( 303 );
			exit;
		}
		$userModel = new UserModel();
		return $userModel->findUserByTokenAccess ( $decodedToken->email );
	}
	
	/**
	 * @param string $email
	 * @param int    $id
	 *
	 * @return array
	 */
	function getSignedJWTForUser ( string $email, int $id ): array {
		$key = Services::getSecretKey ();
		$issuedAtTime = time ();
		$tokenExpiration = strtotime ( '+'.getenv ( 'JWT_TIME_TO_LIVE' ), $issuedAtTime );
		$payload = [
			'email' => $email,
			'iat'   => $issuedAtTime,
			'exp'   => $tokenExpiration,
			'id'    => $id,
		];
		$jwt = JWT::encode ( $payload, $key, 'HS256' );
		return [
			'token'   => $jwt,
			'created' => date ( 'Y-m-d H:i:s', $issuedAtTime ),
			'expires' => date ( 'Y-m-d H:i:s', $tokenExpiration ),
			'id'      => $id, ];
	}
	
	//	function  getIdFromJWTRequest(string $encodedToken) {
	//		$key = Services::getSecretKey ();
	//		$decodedToken = JWT::decode ( $encodedToken, new Key( $key, 'HS256' ) );
	//		$userModel = new UserModel();
	//		return $userModel->findUserByTokenAccess ( $decodedToken->id );
	//	}