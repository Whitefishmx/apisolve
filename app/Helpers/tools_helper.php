<?php
	/**
	 * Genera un número de operación
	 *
	 * @param int $operation Id de operación
	 *
	 * @return string Cadena de 7 números
	 */
	function MakeOperationNumber ( int $operation ): string {
		$trash = '010203040506070809';
		$number = str_pad ( $operation, 7, substr ( str_shuffle ( $trash ), 0, 10 ), STR_PAD_LEFT );
		while ( str_starts_with ( $number, '0' ) ) {
			$number = str_pad ( $operation, 7, substr ( str_shuffle ( $trash ), 0, 10 ), STR_PAD_LEFT );
		}
		return $number;
	}
	
	/**
	 * Permite crear un archivo
	 *
	 * @param string $logName Nombre del archivo log
	 * @param string $message Contenido del Log
	 *
	 * @return bool
	 */
	function createLog ( string $logName, string $message ): bool {
		$logDir = './logs/';
		$logFile = fopen ( $logDir . $logName . '.log', 'a+' );
		if ( $logFile !== FALSE ) {
			$logMessage = '|' . date ( 'Y-m-d H:i:s' ) . '|   ' . $message . "\r\n";
			fwrite ( $logFile, $logMessage );
			fclose ( $logFile );
			return TRUE;
		}
		return FALSE;
	}