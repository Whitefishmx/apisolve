<?php
	if ( !defined ( 'BASEPATH' ) ) exit( 'No direct script access allowed' );
	
	use Config\Services;
	
	/**
	 * Función para obtener los datos necesarios para realizar el proceso de conciliación
	 *
	 * @param object $xml Objeto simplexml cargado
	 *
	 * @return array Devuelve arreglo con la información
	 */
	function XmlProcess ( object $xml ): array {
		$ns = $xml->getNamespaces ( TRUE );
		$xml->registerXPathNamespace ( 't', $ns[ 'tfd' ] );
		$data = [
			'uuid' => (string)$xml->xpath ( '//t:TimbreFiscalDigital' )[ 0 ][ 'UUID' ],
			'fecha' => (string)$xml[ 'Fecha' ],
			'tipo' => (string)$xml[ 'TipoDeComprobante' ],
			'monto' => filter_var ( $xml[ 'Total' ], FILTER_VALIDATE_FLOAT ),
			'moneda' => (string)$xml[ 'Moneda' ],
			'xml' => $xml->saveXML (),
			'receptor' => [
				'rfc' => (string)$xml->xpath ( '//cfdi:Comprobante//cfdi:Receptor ' )[ 0 ][ 'Rfc' ],
				'nombre' => (string)$xml->xpath ( '//cfdi:Comprobante//cfdi:Receptor ' )[ 0 ][ 'Nombre' ],
			],
			'emisor' => [
				'rfc' => (string)$xml->xpath ( '//cfdi:Comprobante//cfdi:Emisor' )[ 0 ][ 'Rfc' ],
				'nombre' => (string)$xml->xpath ( '//cfdi:Comprobante//cfdi:Emisor' )[ 0 ][ 'Nombre' ],
			],
		];
		if ( $xml->xpath ( '//cfdi:CfdiRelacionado' ) ) {
			$data[ 'relacion' ] = (string)$xml->xpath ( '//cfdi:CfdiRelacionado' )[ 0 ][ 'UUID' ];
		}
		return $data;
	}

