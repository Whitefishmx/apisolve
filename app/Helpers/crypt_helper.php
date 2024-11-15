<?php
	function passwordEncrypt ( $password ): bool|string {
		$clave = '5ow3CaNlE4rNtOP1cKOuR53lvEsUp';
		$iv = substr ( implode ( '', array_map ( 'ord', str_split ( 'W#yD0Wh3F4lL' ) ) ), 0, 16 );
		$crypt = openssl_encrypt ( $password, 'ChaCha20', $clave, 0, $iv );
		return ( $crypt );
	}
	
	function decryptData ( $encryptedValue, $iv ): string {
		// Obtener la clave de cifrado del archivo .env
		$encryptionKey = getenv ( 'ENCRYPTION_KEY' );
		// Verificar que la clave sea válida
		if ( $encryptionKey === FALSE ) {
			throw new \RuntimeException( 'Encryption key not found in .env file.' );
		}
		// Método de cifrado utilizado
		$encryptionMethod = 'AES-256-CBC';
		// Desencriptar el valor
		$decryptedValue = openssl_decrypt ( $encryptedValue, $encryptionMethod, $encryptionKey, 0, base64_decode ( $iv ) );
		if ( $decryptedValue === FALSE ) {
			throw new \RuntimeException( 'Decryption failed.' );
		}
		return $decryptedValue;
	}