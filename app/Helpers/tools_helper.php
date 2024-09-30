<?php
	
	use App\Models\DataModel;
	
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
	
	/**
	 * Permite guardar un log en la base de datos
	 *
	 * @param int         $company  id de la compañía
	 * @param int         $user     id de usuario
	 * @param int         $function id de función
	 * @param int         $code     Código de estatus
	 * @param string      $dataIn   JSON con los datos de entrada
	 * @param string|null $dataOut  JSON con los resultados
	 * @param string|NULL $env      Ambiente en el que se va a trabajar
	 *
	 * @return bool resultado
	 */
	function saveLog ( int $user, int $function, int $code, string $dataIn, string $dataOut = NULL ): bool {
		$model = new DataModel();
		$data = [
			'user' => $user,
			'task' => $function,
			'code' => $code,
			'dataIn' => $dataIn,
			'dataOut' => $dataOut,
		];
		return $model->saveLogs ( $data );
	}