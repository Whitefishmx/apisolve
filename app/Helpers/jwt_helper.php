<?php
	
	use Config\Services;
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	use App\Models\UserModel;
	
	/**
	 * @param $authenticationHeader
	 *
	 * @return string
	 * @throws Exception
	 */
	function getJWTFromRequest ( $authenticationHeader ): string {
		if ( is_null ( $authenticationHeader ) ) {
			throw new Exception( 'Token faltante o invalido en la petición' );
		}
		return explode ( ' ', $authenticationHeader )[ 1 ];
	}
	
	/**
	 * @param string $encodedToken
	 *
	 * @return array
	 * @throws Exception
	 */
	function validateJWTFromRequest ( string $encodedToken ): array {
		$key = Services::getSecretKey ();
		$decodedToken = JWT::decode ( $encodedToken, new Key( $key, 'HS256' ) );
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
		];
		$jwt = JWT::encode ( $payload, $key, 'HS256' );
		return [
			'token'   => $jwt,
			'created' => date ( 'Y-m-d H:i:s', $issuedAtTime ),
			'expires' => date ( 'Y-m-d H:i:s', $tokenExpiration ),
			'id'      => $id, ];
	}