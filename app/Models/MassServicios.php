<?php
	
	namespace App\Models;
	
	use App\Controllers\FileController;
	
	class MassServicios extends BaseModel {
		public function registroAfiliado ($user ): bool {
			$url = "https://cartera.masservicios.com.mx/api/v1/registro-afiliado";
			$token = $this->getToken ();
			$folio = $this->generateFolio ( 63, 'employee_benefits', $user[ 'userID' ] );
			$inicio = date ( 'Y-m-d', strtotime ( 'now' ) );
			$fin = date ( 'Y-m-d', strtotime ( '+2 months' ) );
			helper ( 'crypt_helper' );
			$verify = passwordEncrypt ( $folio );
			$data = [
				"cveafiliado"    => $user[ 'rfc' ],
				"poliza"         => "SOLVE_".$folio,
				"rfc"            => $user[ 'rfc' ],
				"nombreAfiliado" => [
					"nombre"    => $user[ 'name' ],
					"apPaterno" => $user[ 'last_name' ],
					"apMaterno" => $user[ 'sure_name' ],
				],
				"telefonos"      => [
					"telefono"  => $user[ 'phone' ],
					"telefono2" => NULL,
				],
				"fechas"         => [
					"inicioVigencia" => $inicio,
					"finVigencia"    => $fin,
				],
				"cuenta"         => (int)142,
				"plan"           => (int)$user[ 'plan' ],
				"verificador"    => $verify,
				"vin"            => "s",
			];
			$res = json_decode ( $this->SendRequest ( $url, 'POST', $data, 'JSON', $token ), TRUE );
			saveLog ( $user[ 'userID' ], 63, 500, json_encode ( $data ), json_encode ( $res ) );
//			var_dump ( $res ); die();
			if ( isset( $res[ 'data' ][ 'cveafiliado' ] ) ) {
				return $this->createCertBenefit ( $user, $res[ 'data' ][ 'cveafiliado' ], $inicio, $fin );
			}
			return FALSE;
		}
		private function getToken () {
			$url = "https://cartera.masservicios.com.mx/api/auth/login";
			$query = "SELECT token, expiration FROM tokens WHERE name = 'mass'";
			if ( !$res = $this->db->query ( $query ) ) {
				$data = [
					"username" => 'user_solve_express',
					"password" => 'fkMZQj0eKOje2RW',
				];
				$resToken = json_decode ( $this->SendRequest ( $url, 'POST', $data, 'x-www-form-urlencoded' ) );
				if ( isset ( $resToken[ "access" ] ) ) {
					$date = date ( 'Y-m-d H:i:s', $resToken[ "data" ][ "expired_at" ] );
					$query = "INSERT INTO tokens (name, url, expiration, active, token)
VALUES ('mass', 'https://cartera.masservicios.com.mx/api/', '$date', '1', '{$resToken['data']['access']}')
ON DUPLICATE KEY UPDATE expiration = '$date', token = '{$resToken['data']['access']}' ";
					$this->db->query ( $query );
					return $resToken[ 'data' ][ 'access' ];
				}
				return FALSE;
			}
			$token = $res->getRow ()->token;
			$expiration = $res->getRow ()->expiration;
			if ( $expiration < time () ) {
				return $token;
			}
			$data = [
				"username" => 'user_solve_express',
				"password" => 'fkMZQj0eKOje2RW',
			];
			$resToken = json_decode ( $this->SendRequest ( $url, 'POST', $data, 'x-www-form-urlencoded' ), TRUE );
//			var_dump ($resToken, isset ( $resToken['data'][ "access" ] ));die();
			if ( isset ( $resToken['data'][ "access" ] ) ) {
				$date = date ( 'Y-m-d H:i:s', strtotime ( $resToken[ "data" ][ "expired_at" ] ));
				$query = "INSERT INTO tokens (name, url, expiration, active, token)
VALUES ('mass', 'https://cartera.masservicios.com.mx/api/', '$date', '1', '{$resToken['data']['access']}')
ON DUPLICATE KEY UPDATE expiration = '$date', token = '{$resToken['data']['access']}' ";
				$this->db->query ( $query );
				return $resToken[ 'data' ][ 'access' ];
			}
			return FALSE;
		}
		/**
		 * @param array  $user
		 * @param        $cveafiliado
		 * @param string $inicio
		 * @param string $fin
		 *
		 * @return bool
		 */
		public function createCertBenefit ( array $user, $cveafiliado, string $inicio, string $fin ): bool {
			$files = new FileController();
			$image = $files->generateCert ( $user[ 'name' ]." ".$user[ 'last_name' ]." ".$user[ 'sure_name' ], $cveafiliado, $inicio, $fin,
				(int)$user[ 'planBenefit' ] );
			saveLog ( $user[ 'userID' ], 63, 500, json_encode ( $user ), json_encode ( $image ) );
			if ( $image ) {
				$query = "UPDATE employee_benefits SET cert = '$image', since = '$inicio', `to` = '$fin' WHERE employee_id = '{$user['employeeId']}', 'active' = 1";
				$this->db->query ( $query );
				return TRUE;
			}
			return FALSE;
		}
		private function getTokenEkus () {
			$url = "https://ekus.masservicios.com.mx/api-113/ws/token/";
			$query = "SELECT token, expiration FROM tokens WHERE name = 'massEkus'";
			if ( !$res = $this->db->query ( $query ) ) {
				$data = [
					"username" => 'user_solve_express',
					"password" => 'fkMZQj0eKOje2RW',
				];
				$resToken = json_decode ( $this->SendRequest ( $url, 'POST', $data ) );
				if ( isset ( $resToken[ "access" ] ) ) {
					$date = date ( 'Y-m-d H:i:s', $resToken[ "data" ][ "expired_at" ] );
					$query = "INSERT INTO tokens (name, url, expiration, active, token)
VALUES ('massEkus', 'https://ekus.masservicios.com.mx/api-113/ws/', '$date', '1', '{$resToken['data']['access']}')
ON DUPLICATE KEY UPDATE expiration = '$date', token = '{$resToken['data']['access']}' ";
					$this->db->query ( $query );
					return $resToken[ 'data' ][ 'access' ];
				}
				return FALSE;
			}
			$token = $res->getRow ()->token;
			$expiration = $res->getRow ()->expiration;
			if ( date ( 'Y-m-d H:i:s', $expiration ) < date ( 'Y-m-d H:i:s', strtotime ( 'now' ) ) ) {
				return $token;
			}
			$data = [
				"username" => 'user_solve_express',
				"password" => 'fkMZQj0eKOje2RW',
			];
			$resToken = json_decode ( $this->SendRequest ( $url, 'POST', $data, 'x-www-form-urlencoded' ) );
			if ( isset ( $resToken[ "access" ] ) ) {
				$date = date ( 'Y-m-d H:i:s', $resToken[ "data" ][ "expired_at" ] );
				$query = "INSERT INTO tokens (name, url, expiration, active, token)
VALUES ('massEkus', 'https://ekus.masservicios.com.mx/api-113/ws/', '$date', '1', '{$resToken['data']['access']}')
ON DUPLICATE KEY UPDATE expiration = '$date', token = '{$resToken['data']['access']}'";
				$this->db->query ( $query );
				return $resToken[ 'data' ][ 'access' ];
			}
			return FALSE;
		}
		/** @noinspection PhpSameParameterValueInspection */
		private function SendRequest ( $endpoint, $method = 'POST', $data = [], $dataType = 'JSON', $token = NULL ): bool|string {
			$curl = curl_init ();
			$headers = [];
			$postData = NULL;
			if ( $dataType === 'JSON' ) {
				$headers[] = 'Content-Type: application/json';
				$postData = json_encode ( $data );
			} else if ( $dataType === 'x-www-form-urlencoded' ) {
				$headers[] = 'Content-Type: application/x-www-form-urlencoded';
				$postData = http_build_query ( $data );
			}
			if ( !empty( $token ) ) {
				$headers[] = "Authorization: Bearer $token";
			}
			$curlOptions = [
				CURLOPT_URL            => $endpoint,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_FOLLOWLOCATION => TRUE,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => $method,
				CURLOPT_HTTPHEADER     => $headers,
			];
			if ( in_array ( strtoupper ( $method ), [ 'POST', 'PUT', 'PATCH' ] ) ) {
				$curlOptions[ CURLOPT_POSTFIELDS ] = $postData;
			}
			curl_setopt_array ( $curl, $curlOptions );
			$response = curl_exec ( $curl );
			if ( curl_errno ( $curl ) ) {
				$error_msg = curl_error ( $curl );
				curl_close ( $curl );
				$error = 500;
				$resp = [ 'error' => $error, 'error_description' => $error_msg ];
				return json_encode ( $resp );
				
			}
			curl_close ( $curl );
			return $response;
		}
	}