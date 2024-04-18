<?php
	
	namespace App\Models;
	
	use OpenSSLAsymmetricKey;
	use CodeIgniter\Model;
	use Config\Database;

//	use Exception;
	class Stp extends Model {
		private string $privateKey = './crypt/keyFish.pem';
		private string $passphrase = 'C0ntR4S3NIa4F1nt3CHACc355';
		private string $environment = '';
		private string $APISandbox = '';
		private string $APILive = '';
		public string $base = '';
		private string $stpSandbox = 'https://demo.stpmex.com:7024/speiws/rest/';
		private string $stpLive = 'https://demo.stpmex.com:7024/speiws/rest/';
		public function __construct () {
			parent::__construct ();
			require 'conf.php';
			$this->base = $this->environment === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$this->db = Database::connect ( 'default' );
		}
		/**
		 * Genera un dispersion de dinero a través de STP
		 *
		 * @param string|NULL $env Ambiente en el que se estará trabajando
		 *
		 * @return bool|string resultado de la petición
		 */
		public function dispersion ( string $env = NULL ): bool|string {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$data = [
				'bancoReceptor' => '90646',
				'empresa' => 'WHITEFISH',
				'fechaOperacion' => date ( 'Ymd', strtotime ( 'now' ) ),
				'folioOrigen' => '123456789',
				'claveRastreo' => '0034501',
				'bancoOrigen' => '90646',
				'monto' => '20.00',
				'tipoPago' => 1,
				'tipoCuentaOrigen' => 40,
				'nombreOrigen' => 'NombreOrigen',
				'cuentaOrigen' => '646180546900000003',
				'rfcOrigen' => 'WST230202CQ2',
				'tipoCuentaDestino' => 40,
				'nombreDestino' => 'NombreDestino',
				'cuentaDestino' => '646180110400000007',
				'rfcDestino' => 'WST230202CQ2',
				'emailBenef' => '',
				'tipoCuentaBenef2' => '',
				'nombreBenef2' => '',
				'cuentaBenef2' => '',
				'rfcBenef2' => '',
				'concepto' => "PruebaREST",
				'concepto2' => '',
				'claveCat1' => '',
				'claveCat2' => '',
				'clavePAgo' => '',
				'refCobranza' => '',
				'refNumeric' => '0034501',
				'tipoOperacion' => '',
				'topological' => '',
				'usuario' => '',
				'medioEntrega' => '',
				'prioridad' => '',
				'iva' => '',
			];
			$cadenaOriginal = implode ( '|', $data );
			$cadenaOriginal = '||' . $cadenaOriginal . '||';
			var_dump ( $cadenaOriginal );
			$cadenaOriginal = $this->getSign ( $cadenaOriginal );
			var_dump ( $cadenaOriginal );
			$body = [
				"claveRastreo" => $data[ 'folioOrigen' ],
				"conceptoPago" => $data[ 'concepto' ],
				"cuentaOrdenante" => $data[ 'cuentaOrigen' ],
				"cuentaBeneficiario" => $data[ 'cuentaDestino' ],
				"empresa" => $data[ 'empresa' ],
				"institucionContraparte" => $data[ 'bancoReceptor' ],
				"institucionOperante" => $data[ 'bancoOrigen' ],
				"monto" => $data[ 'monto' ],
				"nombreBeneficiario" => $data[ 'nombreDestino' ],
				"nombreOrdenante" => $data[ 'nombreOrigen' ],
				"referenciaNumerica" => $data[ 'refNumerica' ],
				"rfcCurpBeneficiario" => $data[ 'rfcDestino' ],
				"rfcCurpOrdenante" => $data[ 'rfcOrigen' ],
				"tipoCuentaBeneficiario" => $data[ 'tipoCuentaDestino' ],
				"tipoCuentaOrdenante" => $data[ 'tipoCuentaOrigen' ],
				"tipoPago" => $data[ 'tipoPago' ],
				"firma" => $cadenaOriginal,
			];
			var_dump ( json_encode ( $body ) );
			return $this->sendRequest ( 'ordenPago/registra', $body, $env, 'PUT', 'JSON' );
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
		 * Enviar peticiones a través de CURL al api rest de STP
		 *
		 * @param string      $endpoint Endpoint a utilizar
		 * @param mixed       $data     información a enviar
		 * @param string      $env      Ambiente a ejecutar
		 * @param string|null $method   Método HTTP para enviar la petición
		 * @param string|null $dataType Tipo de información que se enviara
		 *
		 * @return bool|string resultado de la petición
		 */
		public function sendRequest ( string $endpoint, mixed $data, string $env, ?string $method, ?string $dataType ): bool|string {
			$env = strtoupper ( $env ) ?? 'SANDBOX';
			$url = ( $env == 'SANDBOX' ) ? $this->stpSandbox : $this->stpLive;
			$method = !empty( $method ) ? strtoupper ( $method ) : 'POST';
			$data = json_encode ( $data );
			$headers = [];
			if ( strtoupper ( $dataType ) === 'JSON' ) {
				$headers[] = 'Content-Type: application/json; charset=utf-8';
			}
			if ( ( $ch = curl_init () ) ) {
				curl_setopt ( $ch, CURLOPT_URL, $url . $endpoint );
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