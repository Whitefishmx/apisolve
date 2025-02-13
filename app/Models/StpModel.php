<?php
	
	namespace App\Models;
	
	use OpenSSLAsymmetricKey;
	
	class StpModel extends BaseModel {
		//		private string $privateKey = './crypt/llavePrivada.pem';
		private string $privateKey = 'C:\web\apps\apisolve\public\crypt/llavePrivada.pem';
		private string $passphrase = '12345678';
		private string $stpSandbox = 'https://demo.stpmex.com:7024/speiws/rest/';
		private string $stpLive    = 'https://demo.stpmex.com:7024/speiws/rest/';
		/**
		 * Genera un dispersion de dinero a través de STP
		 *
		 * @param string|NULL $env Ambiente en el que se estará trabajando
		 *
		 * @return bool|string resultado de la petición
		 */
		public function sendDispersion ( array $args, ?string $referenceNum = NULL, ?string $folio = NULL, ?int $user = NULL ): bool|string {
			$url = $this->stpSandbox.'ordenPago/registra';
			if ( $referenceNum === NULL ) {
				helper ( 'tools_helper' );
				$referenceNum = MakeOperationNumber ( $this->getNexId ( 'logs' ) );
			}
			if ( $folio === NULL ) {
				helper ( 'tetraoctal_helper' );
				$folio = $this->generateFolio ( 10, 'logs', 2 );
			}
			$bancoBeneficiario = $this->getBankByClave ( $args[ 'beneficiario' ][ 'clabe' ] )[ 1 ];
			$bancoOrdenante = $this->getBankByClave ( $args[ 'beneficiario' ][ 'clabe' ] )[ 1 ];
			helper ( 'tools_helper' );
			$data = [
				'bancoReceptor'     => $bancoBeneficiario[ 'bnk_code' ],
				'empresa'           => 'WHITEFISH',
				'fechaOperacion'    => '',
				'folioOrigen'       => '',
				'claveRastreo'      => $folio,
				'bancoOrigen'       => $bancoOrdenante[ 'bnk_code' ],
				'monto'             => number_format ( strval ( $args[ 'beneficiario' ][ 'monto' ] ), 2 ),
				'tipoPago'          => 1,
				'tipoCuentaOrigen'  => 40,
				'nombreOrigen'      => $args[ 'ordenante' ][ 'nombre' ],
				'cuentaOrigen'      => $args[ 'ordenante' ][ 'clabe' ],
				'rfcOrigen'         => 'ND',
				'tipoCuentaDestino' => 40,
				'nombreDestino'     => $args[ 'beneficiario' ][ 'nombre' ],
				'cuentaDestino'     => $args[ 'beneficiario' ][ 'clabe' ],
				'rfcDestino'        => 'ND',
				'emailBenef'        => '',
				'tipoCuentaBenef2'  => '',
				'nombreBenef2'      => '',
				'cuentaBenef2'      => '',
				'rfcBenef2'         => '',
				'concepto'          => $args[ 'beneficiario' ][ 'concepto' ],
				'concepto2'         => '',
				'claveCat1'         => '',
				'claveCat2'         => '',
				'clavePAgo'         => '',
				'refCobranza'       => '',
				'refNumeric'        => $referenceNum,
				'tipoOperacion'     => '',
				'topological'       => '',
				'usuario'           => '',
				'medioEntrega'      => '',
				'prioridad'         => '',
				'iva'               => '',
			];
			$cadenaOriginal = implode ( '|', $data );
			$cadenaOriginal = '||'.$cadenaOriginal.'||';
			saveLog ( 2, 1, 1, 200, json_encode ( [ 'cadenaOriginal' => $data ] ) );
			$cadenaOriginal = $this->getSign ( $cadenaOriginal );
			$body = [
				"claveRastreo"           => $data[ 'claveRastreo' ],
				"conceptoPago"           => $data[ 'concepto' ],
				"cuentaOrdenante"        => $data[ 'cuentaOrigen' ],
				"cuentaBeneficiario"     => $data[ 'cuentaDestino' ],
				"empresa"                => $data[ 'empresa' ],
				"institucionContraparte" => $data[ 'bancoReceptor' ],
				"institucionOperante"    => $data[ 'bancoOrigen' ],
				"monto"                  => $data[ 'monto' ],
				"nombreBeneficiario"     => $data[ 'nombreDestino' ],
				"nombreOrdenante"        => $data[ 'nombreOrigen' ],
				"referenciaNumerica"     => $data[ 'refNumeric' ],
				"rfcCurpBeneficiario"    => $data[ 'rfcDestino' ],
				"rfcCurpOrdenante"       => $data[ 'rfcOrigen' ],
				"tipoCuentaBeneficiario" => "{$data[ 'tipoCuentaDestino' ]}",
				"tipoCuentaOrdenante"    => "{$data[ 'tipoCuentaOrigen' ]}",
				"tipoPago"               => "{$data[ 'tipoPago' ]}",
				"firma"                  => "$cadenaOriginal",
			];
			$res = $this->sendRequest ( $url, $body, 'PUT', 'JSON' );
			saveLog ( 2, 1, 1, json_encode ( $body ), json_encode ( $res ) );
			return $res;
		}
		/**
		 * Obtiene los movimientos de la cuenta
		 *
		 * @param string|null $date
		 * @param string      $tipoOrden
		 * @param string|NULL $env
		 *
		 * @return bool|string
		 */
		public function sendConsulta ( ?string $date = NULL, ?string $tipoOrden = 'E', ?string $env = NULL ): bool|string {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$url = 'https://efws-dev.stpmex.com/efws/API/V2/conciliacion';
			$date = date ( 'Ymd', $date );
			$data = [
				'empresa'   => 'WHITEFISH',
				'tipoOrden' => $tipoOrden,
				'date'      => $date,
			];
			$cadenaOriginal = implode ( '|', $data );
			$cadenaOriginal = '||'.$cadenaOriginal.'||';
			$cadenaOriginal = $this->getSign ( $cadenaOriginal );
			$body = [
				"empresa"        => $data[ 'empresa' ],
				"firma"          => $cadenaOriginal,
				"page"           => 0,
				"tipoOrden"      => $tipoOrden,
				"fechaOperacion" => $date,
			];
			return $this->sendRequest ( $url, $body, 'POST', 'JSON' );
		}
		/**
		 * Genera la firma de la llave
		 *
		 * @param string $cadenaOriginal Cadena separada por pipes a ser firmada
		 *
		 * @return string cadena firmada
		 */
		public function getSign ( string $cadenaOriginal ): string {
			$privateKey = $this->getCertified ();
			$binarySign = "";
			openssl_sign ( $cadenaOriginal, $binarySign, $privateKey, "RSA-SHA256" );
			return base64_encode ( $binarySign );
		}
		/**
		 * Obtiene el certificado para poder realizar la firma
		 * @return OpenSSLAsymmetricKey|bool
		 */
		private function getCertified (): OpenSSLAsymmetricKey|bool {
			$fp = fopen ( realpath ( $this->privateKey ), "r" );
			$privateKey = fread ( $fp, filesize ( realpath ( $this->privateKey ) ) );
			fclose ( $fp );
			return openssl_get_privatekey ( $privateKey, $this->passphrase );
		}
		/**
		 * Obtiene el ID de una compañía por su clabe bancaria
		 *
		 * @param string $clabe Clabe bancaria a buscar
		 * @param string $env   Ambiente en el que se va a trabajar
		 *
		 * @return array Resultados
		 */
		public function validateClabe ( string $clabe, ?string $env = 'SANDBOX' ): array {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "SELECT companie_id FROM apisandbox_sandbox.fintech_clabes WHERE fintech_clabe = '$clabe'";
			$res = $this->db->query ( $query );
			if ( $res->getNumRows () < 1 ) {
				return [ FALSE, 'Clabe no encontrada' ];
			}
			return $res->getResultArray ();
		}
		/**
		 * Enviar peticiones a través de CURL al api rest de STP
		 *
		 * @param string      $url
		 * @param mixed       $data     información a enviar
		 * @param string|null $method   Método HTTP para enviar la petición
		 * @param string|null $dataType Tipo de información que se enviara
		 *
		 * @return bool|string resultado de la petición
		 */
		public function sendRequest ( string $url, mixed $data, ?string $method, ?string $dataType ): bool|string {
			$method = !empty( $method ) ? strtoupper ( $method ) : 'POST';
			$data = json_encode ( $data );
			$headers = [];
			if ( strtoupper ( $dataType ) === 'JSON' ) {
				$headers[] = 'Content-Type: application/json; charset=utf-8';
			}
			$responseHeaders = [];
			if ( ( $ch = curl_init () ) ) {
				curl_setopt ( $ch, CURLOPT_URL, $url );
				curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
				curl_setopt ( $ch, CURLOPT_ENCODING, '' );
				curl_setopt ( $ch, CURLOPT_MAXREDIRS, 10 );
				curl_setopt ( $ch, CURLOPT_TIMEOUT, 0 );
				curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
				curl_setopt ( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
				if ( $method == 'POST' ) {
					curl_setopt ( $ch, CURLOPT_POST, TRUE );
				} else {
					curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, $method );
				}
				curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
				curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
				$response = curl_exec ( $ch );
				$code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
				if ( $code !== 200 ) {
					curl_close ( $ch );
					$resp = [ 'error' => $code, 'error_description' => 'STPTransport', 'reason' => $response ];
					$response = json_encode ( $resp );
				}
				curl_close ( $ch );
			} else {
				$resp = [ 'error' => 500, 'error_description' => 'STPTransport', 'reason' => 'No se logro realizar la comunicación con STP' ];
				$response = json_encode ( $resp );
			}
			return $response;
		}
	}