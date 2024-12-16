<?php
	function passwordEncrypt ( $password ): bool|string {
		$clave = getenv ( 'ENCRYPTION_KEY' ); //'5ow3CaNlE4rNtOP1cKOuR53lvEsUp'
		$iv = substr ( implode ( '', array_map ( 'ord', str_split ( 'W#yD0Wh3F4lL' ) ) ), 0, 16 );
		$crypt = openssl_encrypt ( $password, 'ChaCha20', $clave, 0, $iv );
		return ( $crypt );
	}
	
	function encryptValue ( $value, $key, $iv ): false|string {
		$encryptionMethod = 'AES-256-CBC';
		return openssl_encrypt ( $value, $encryptionMethod, $key, 0, $iv );
	}
	
	function decryptValue ( $encryptedValue, $key, $iv ): false|string {
		$encryptionMethod = 'AES-256-CBC';
		return openssl_decrypt ( $encryptedValue, $encryptionMethod, $key, 0, $iv );
	}
	
	function generateIV ( $seed ): string {
		return substr ( hash ( 'sha256', $seed ), 0, openssl_cipher_iv_length ( 'AES-256-CBC' ) );
	}
	
	function getRecoveryCode (): array {
		$arg1 = str_shuffle ( strtotime ( 'now' ) );
		$arg2 = str_shuffle ( base64_encode ( $arg1 ) );
		$trash = strtoupper ( str_replace ( '=', '', str_shuffle ( $arg1.$arg2 ) ) );
		$code = substr ( $trash, rand ( 0, ( strlen ( $trash ) - 6 ) ), 6 );
		return [ 'code' => $code, 'cypher' => passwordEncrypt ( $code ) ];
	}
	function getPasswordCode (): array {
		$arg1 = str_shuffle ( base64_encode(strtotime ( 'now' ).getenv ('TRASH_CODE')) );
		$arg2 = str_shuffle ( base64_encode ( $arg1 ) );
		$trash = strtoupper ( str_replace ( '=', '', str_shuffle ( $arg1.$arg2 ) ) );
		$code = substr ( $trash, rand ( 0, ( strlen ( $trash ) - 32 ) ), 32 );
		return [ 'code' => $code, 'cypher' => passwordEncrypt ( $code ) ];
	}