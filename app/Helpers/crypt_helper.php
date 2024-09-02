<?php
	function passwordEncrypt ( $password ): bool|string {
		$clave = '5ow3CaNlE4rNtOP1cKOuR53lvEsUp';
		$iv =  substr(implode('', array_map('ord', str_split('W#yD0Wh3F4lL'))), 0, 16);
		$crypt = openssl_encrypt ( $password, 'ChaCha20', $clave, 0, $iv );
		return  ( $crypt );
	}