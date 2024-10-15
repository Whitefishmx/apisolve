<?php
	/**
	 * Transforma una cifra del sistema decimal a uno basé 32
	 *
	 * @param int $numero decimal que se va a convertir
	 *
	 * @return int|string número base 32
	 */
	function encode32 ( int $numero ): int|string {
		$base32 = '';
		$base = 32;
		$caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUV';
		if ( $numero === 0 ) {
			return 0;
		}
		while ( $numero > 0 ) {
			$residuo = $numero % $base;
			$base32 = $caracteres[ $residuo ] . $base32;
			$numero = floor ( $numero / $base );
		}
		return $base32;
	}
	
	/**
	 * Pasa un numero en base32 a uno decimal.
	 *
	 * @param string $base32 Número base32 a decodificar
	 *
	 * @return int Retorna un numero entero decimal
	 */
	function decode32 ( string $base32 ): int {
		$decimal = 0;
		$base = 32;
		$caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUV';
		$base32 = strtoupper ( strval ( $base32 ) );
		$longitud = strlen ( $base32 );
		for ( $i = 0; $i < $longitud; $i++ ) {
			$position = strpos ( $caracteres, $base32[ $i ] );
			$decimal += $position * pow ( $base, $longitud - $i - 1 );
		}
		return $decimal;
	}
	
	/**
	 * Divide un string serializado en base32 en un arreglo con las cifras aún en base32
	 *
	 * @param string $base32 serial en base32
	 *
	 * @return array arreglo de cifras en base32
	 */
	function split32 ( string $base32 ): array {
		$base32 = strtoupper ( strval ( $base32 ) );
		$delimiters = 'WXYZ';
		$cadena = str_replace ( str_split ( $delimiters ), ',', $base32 );
		return explode ( ',', $cadena );
	}
	
	/**
	 * Divide el serial en un arreglo con las cifras ya convertidas a base 10
	 *
	 * @param string $base32 serial base32
	 *
	 * @return array arreglo en base10
	 */
	function deserialize32 ( string $base32 ): array {
		$base32 = strtoupper ( strval ( $base32 ) );
		$delimiters = 'WXYZ';
		$cadena = str_replace ( str_split ( $delimiters ), ',', $base32 );
		$array32 = explode ( ',', $cadena );
		$array10 = [];
		for ( $i = 0; $i < count ( $array32 ); $i++ ) {
			$array10[] = decode32 ( $array32[ $i ] );
		}
		return $array10;
	}
	
	/**
	 * Genera un serial con cifras en base32 a partir de un arreglo con números en base10
	 *
	 * @param array $data
	 *
	 * @return string serial en base32
	 */
	function serialize32 ( array $data ): string {
		$binded = '';
		$delimiters = 'WXYZ';
		for ( $i = 0; $i < count ( $data ); $i++ ) {
			$binded .= encode32 ( $data[ $i ] ) . substr ( str_shuffle ( $delimiters ), 0, 1 );
		}
		$binded = substr ( $binded, 0, -1 );
		return $binded;
	}