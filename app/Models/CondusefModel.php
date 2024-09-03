<?php
	
	namespace App\Models;
	
	use CodeIgniter\Model;
	
	require 'condusefData.php';
	
	class CondusefModel extends BaseModel {
		private array $url = [
			'reune'  => [
				'development' => 'https://api-reune-pruebas.condusef.gob.mx',
				'production'  => 'https://api-reune.condusef.gob.mx/' ],
			'redeco' => [
				'development' => 'https://api.condusef.gob.mx/',
				'production'  => 'https://api.live.condusef.gob.mx/' ] ];
		public function getReuneToken () {
			$query = "SELECT expiration, token FROM tokens WHERE name = 'reune'";
			$res = $this->db->query ( $query );
			if ( $res->getNumRows () > 0 ) {
				if ( strtotime ( $res->getResultArray ()[ 0 ][ 'expiration' ] ) < strtotime ( 'now' ) ) {
					return $res->getResultArray ()[ 0 ][ 'token' ];
				}
			}
			$data = [
				"username" => "SuperVatoro",
				"password" => '$VatoroCIC2024$' ];
			$token = json_decode ( $this->sendRequest ( 'reune', 'auth/users/token/', $data, 'GET' ), TRUE );
			if ( isset( $token[ 'user' ] ) ) {
				$expiration = date ( 'Y-m-d H:i:s', strtotime ( '+30 days' ) );
				$env = strtolower ( getenv ( 'CI_ENVIRONMENT' ) );
				$url = $this->url[ 'reune' ][ $env ];
				$query = "INSERT INTO tokens (name, url, expiration, active, token)
values ('reune', '$url', '$expiration', 1, '{$token['user']['token_access']}')
ON DUPLICATE KEY UPDATE expiration = '$expiration', active = '1', token = '{$token['user']['token_access']}'";
				$this->db->query ( $query );
				return $token[ 'user' ][ 'token_access' ];
			}
			return FALSE;
		}
		public function getRedecoToken () {
			$query = "SELECT expiration, token FROM tokens WHERE name = 'redeco'";
			$res = $this->db->query ( $query );
			if ( $res->getNumRows () > 0 ) {
				return $res->getResultArray ()[ 0 ][ 'token' ];
			}
			$data = [
				"username"         => "SuperVatoro3",
				"password"         => 'VatoroCIC2024$',
				"confirm_password" => '$VatoroCIC2024$' ];
			$token = $this->sendRequest ( 'redeco', 'auth/users/token/', $data, 'GET' );
			if ( isset( $token[ 'user' ] ) ) {
				$expiration = date ( 'Y-m-d H:i:s', strtotime ( '+30 days' ) );
				$env = strtolower ( getenv ( 'CI_ENVIRONMENT' ) );
				$query = "INSERT INTO tokens (name, url, expiration, active, token)
values ('redeco', '{ $this->url[ 'reune'][$env ]}', '$expiration', 1, '{$token['user']['token_access']}')
ON DUPLICATE KEY UPDATE expiration = '$expiration', active = '1', token = '{$token['user']['token_access']}'";
				$this->db->query ( $query );
				return $token[ 'user' ][ 'token_access' ];
			}
			return FALSE;
		}
		public function postReuneGrievance ( array $args ) {
			$token = $this->getReuneToken ();
			if ( $token === FALSE ) {
				return FALSE;
			}
			$data = [];
			foreach ( $args as $row ) {
				$folio = $this->newFolio ( 'reune' );
				$data[] = [
					"InstitucionClave"        => institucion,
					"Sector"                  => sector,
					"ConsultasTrim"           => 1,
					"NumConsultas"            => 1,
					"ConsultasFolio"          => $folio,
					"ConsultasEstatusCon"     => 1,
					"ConsultasFecAten"        => NULL,
					"EstadosId"               => 28,
					"ConsultasFecRecepcion"   => $row[ 'recepcion' ],
					"MediosId"                => intval ( $row[ 'medio' ] ),
					"Producto"                => "026911811258",
					"CausaId"                 => $row[ 'cusa' ],
					"ConsultasCP"             => 87330,
					"ConsultasMpioId"         => 22,
					"ConsultasLocId"          => 9,
					"ConsultasColId"          => 298,
					"ConsultascatnivelatenId" => NULL,
					"ConsultasPori"           => "SI",
				];
			}
			//			var_dump ( json_encode ( $data ) );
			//			die();
			$res = $this->sendRequest ( 'reune', 'reune/consultas/general', $data, 'POST', $token );
			$session = session ();
			$user = $session->get ( 'user' );
			$user = $user === NULL ? 2 : $user;
			$data = json_encode ( $data );
			$query = "INSERT INTO reune (user_id, inData, response) VALUES ($user, '$data', '$res')";
			$this->db->query ( $query );
			$res = json_decode ( $res, TRUE );
			if ( isset( $res[ 'errors' ] ) ) {
				return FALSE;
			}
			return $res;
		}
		public function postRedecoGrievance ( array $args ) {
			$token = $this->getRedecoToken ();
			if ( $token === FALSE ) {
				return FALSE;
			}
			$data = [];
			foreach ( $args as $row ) {
				$folio = $this->newFolio ( 'reune' );
				$data[] = [
					"QuejasDenominacion"    => institucion,
					"QuejasSector"          => sector,
					"QuejasNoMes"           => 3,
					"QuejasNum"             => 1,
					"QuejasFolio"           => $folio,
					"QuejasFecRecepcion"    => $row[ 'recepcion' ],
					"QuejasMedio"           => intval ( $row[ 'medio' ] ),
					"QuejasNivelAT"         => 1,
					"QuejasProducto"        => "026911801257",
					"QuejasCausa"           => $row[ 'causa' ],
					"QuejasPORI"            => "NO",
					"QuejasEstatus"         => 2,
					"QuejasEstados"         => NULL,
					"EstadosId"             => 28,
					"QuejasMunId"           => 12,
					"QuejasLocId"           => NULL,
					"QuejasColId"           => 2175,
					"QuejasCP"              => 14390,
					"QuejasTipoPersona"     => 2,
					"QuejasSexo"            => NULL,
					"QuejasEdad"            => NULL,
					"QuejasFecResolucion"   => $row[ 'resolucion' ],
					"QuejasFecNotificacion" => $row[ 'notificacion' ],
					"QuejasRespuesta"       => 1,
					"QuejasNumPenal"        => NULL,
					"QuejasPenalizacion"    => NULL,
				];
			}
			$res = $this->sendRequest ( 'redeco', 'redeco/quejas', $data, 'POST', $token );
			$session = session ();
			$user = $session->get ( 'user' );
			$user = $user === NULL ? 2 : $user;
			$data = json_encode ( $data );
			if ( !$this->validateJSON ( $res ) ) {
				$res = json_encode ( [ 'errors' => $res ] );
			}
			$query = "INSERT INTO reune (user_id, inData, response) VALUES ($user, '$data', '$res')";
			$this->db->query ( $query );
			$res = json_decode ( $res, TRUE );
			if ( isset( $res[ 'errors' ] ) ) {
				return FALSE;
			}
			return $res;
		 }
		public function postReuneClaims ( array $args ) {
			$token = $this->getReuneToken ();
			if ( $token === FALSE ) {
				return FALSE;
			}
			$data = [];
			foreach ( $args as $row ) {
				$folio = $this->newFolio ( 'reune' );
				$data[] = [
					"RecDenominacion"        => institucion,
					"RecSector"              => sector,
					"RecTrimestre"           => 1,
					"RecNumero"              => 1,
					"RecFolioAtencion"       => $folio,
					"RecEstadoConPend"       => 2,
					"RecFechaReclamacion"    => $row[ 'recepcion' ],
					"RecFechaAtencion"       => $row[ 'atencion' ],
					"RecMedioRecepcionCanal" => intval ( $row[ 'medio' ] ),
					"RecProductoServicio"    => "026911791256",
					"RecCausaMotivo"         => $row[ 'causa' ],
					"RecFechaResolucion"     => $row[ 'resolucion' ],
					"RecFechaNotifiUsuario"  => $row[ 'notificacion' ],
					"RecEntidadFederativa"   => 9,
					"RecCodigoPostal"        => 9070,
					"RecMunicipioAlcaldia"   => 7,
					"RecLocalidad"           => 9,
					"RecColonia"             => NULL,
					"RecMonetario"           => "SI",
					"RecMontoReclamado"      => 1,
					"RecImporteAbonado"      => 1,
					"RecFechaAbonoImporte"   => "22/03/2024",
					"RecPori"                => "NO",
					"RecTipoPersona"         => 1,
					"RecSexo"                => "H",
					"RecEdad"                => 18,
					"RecSentidoResolucion"   => 1,
					"RecNivelAtencion"       => 1,
					"RecFolioCondusef"       => NULL,
					"RecReversa"             => NULL,
				];
			}
			//			var_dump ( json_encode ( $data ) );
			//			die();
			$res = $this->sendRequest ( 'reune', 'reune/reclamaciones/general', $data, 'POST', $token );
			$session = session ();
			$user = $session->get ( 'user' );
			$user = $user === NULL ? 2 : $user;
			$data = json_encode ( $data );
			$query = "INSERT INTO reune (user_id, inData, response) VALUES ($user, '$data', '$res')";
			$this->db->query ( $query );
			$res = json_decode ( $res, TRUE );
			if ( isset( $res[ 'errors' ] ) ) {
				return FALSE;
			}
			return $res;
		}
		public function postReuneClarifications ( array $args ) {
			$token = $this->getReuneToken ();
			if ( $token === FALSE ) {
				return FALSE;
			}
			$data = [];
			foreach ( $args as $row ) {
				$folio = $this->newFolio ( 'reune' );
				$data[] = [
					"AclaracionDenominacion"        => institucion,
					"AclaracionSector"              => sector,
					"AclaracionTrimestre"           => 1,
					"AclaracionNumero"              => 1,
					"AclaracionFolioAtencion"       => $folio,
					"AclaracionEstadoConPend"       => 2,
					"AclaracionFechaAclaracion"     => $row[ 'aclaracion' ],
					"AclaracionFechaAtencion"       => $row[ 'atencion' ],
					"AclaracionMedioRecepcionCanal" => intval ( $row[ 'medio' ] ),
					"AclaracionProductoServicio"    => "026911791256",
					"AclaracionCausaMotivo"         => $row[ 'causa' ],
					"AclaracionFechaResolucion"     => $row[ 'resolucion' ],
					"AclaracionFechaNotifiUsuario"  => $row[ 'notificacion' ],
					"AclaracionEntidadFederativa"   => 9,
					"AclaracionCodigoPostal"        => 14390,
					"AclaracionMunicipioAlcaldia"   => 12,
					"AclaracionLocalidad"           => 9,
					"AclaracionColonia"             => 2175,
					"AclaracionMonetario"           => "NO",
					"AclaracionMontoReclamado"      => NULL,
					"AclaracionPori"                => "SI",
					"AclaracionTipoPersona"         => 1,
					"AclaracionSexo"                => "H",
					"AclaracionEdad"                => 18,
					"AclaracionNivelAtencion"       => 1,
					"AclaracionFolioCondusef"       => NULL,
					"AclaracionReversa"             => NULL,
					"AclaracionOperacionExtranjero" => "SI",
				];
			}
			$res = $this->sendRequest ( 'reune', 'reune/aclaraciones/general', $data, 'POST', $token );
			$session = session ();
			$user = $session->get ( 'user' );
			$user = $user === NULL ? 2 : $user;
			$data = json_encode ( $data );
			$query = "INSERT INTO reune (user_id, inData, response) VALUES ($user, '$data', '$res')";
			$this->db->query ( $query );
			$res = json_decode ( $res, TRUE );
			if ( isset( $res[ 'errors' ] ) ) {
				return FALSE;
			}
			return $res;
		}
		private function newFolio ( string $origin ): string {
			helper ( 'tetraoctal_helper' );
			$id = $this->getNexId ( $origin );
			$session = session ();
			$user = $session->get ( 'user' );
			$user = $user === NULL ? 2 : $user;
			$folio = [ strtotime ( 'now' ), $id, $user ];
			return preFolio.serialize32 ( $folio );
		}
		private function sendRequest ( string $url, string $endpoint, array $data, ?string $method, string $token =
		NULL ):
		bool|string {
			$headers = [];
			$env = getenv ( 'CI_ENVIRONMENT' );
			$url = $this->url[ $url ][ strtolower ( $env ) ];
			$method = !empty( $method ) ? strtoupper ( $method ) : 'POST';
			$headers[] = 'Content-Type: application/json; charset=utf-8';
			if ( $token != NULL ) {
				$headers[] = "Authorization: $token";
			}
			$data = json_encode ( $data );
			if ( ( $ch = curl_init () ) ) {
				curl_setopt ( $ch, CURLOPT_URL, $url.'/'.$endpoint );
				curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
				curl_setopt ( $ch, CURLOPT_ENCODING, '' );
				curl_setopt ( $ch, CURLOPT_MAXREDIRS, 10 );
				curl_setopt ( $ch, CURLOPT_TIMEOUT, 0 );
				curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
				curl_setopt ( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
				curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE ); // Desactiva la verificación del certificado SSL
				curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
				if ( $method == 'POST' ) {
					curl_setopt ( $ch, CURLOPT_POST, TRUE );
				} else {
					curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, $method );
				}
				curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
				curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
				$response = curl_exec ( $ch );
				curl_close ( $ch );
				if ( $response === '' || $response === FALSE ) {
					return curl_error ( $ch );
				}
				return $response;
			}
			return FALSE;
		}
		function validateJSON ( string $json ): bool {
			try {
				$test = json_decode ( $json, NULL, JSON_THROW_ON_ERROR );
				if ( is_object ( $test ) ) return TRUE;
				return FALSE;
			} catch ( Exception $e ) {
				return FALSE;
			}
		}
	}
