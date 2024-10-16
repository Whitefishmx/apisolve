<?php
	
	namespace App\Models;
	class CepModel extends BaseModel {
		public function DownloadCEP ( array $args, int $try, string $env ) {
			sleep ( rand ( 5, 10 ) );
			$data = [
				'tipoCriterio' => 'T',
				'receptorParticipante' => 0,
				'captcha' => 'c',
				'tipoConsulta' => 1,
				'fecha' => date ( 'd-m-Y', strtotime ( $args[ 'transactionDate' ] ) ),
				'criterio' => $args[ 'trakingKey' ],
				'emisor' => $sourceBank[ 0 ][ 'bnk_code' ],
				'receptor' => $receiverBank[ 0 ][ 'bnk_code' ],
				'cuenta' => $args[ 'receiverClabe' ],
				'monto' => $args[ 'amount' ],
			];
			if ( $ch = curl_init () ) {
				curl_setopt ( $ch, CURLOPT_URL, "https://www.banxico.org.mx/cep/valida.do" );
				curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
				curl_setopt ( $ch, CURLOPT_TIMEOUT, 200 );
				curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
				curl_setopt ( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
				curl_setopt ( $ch, CURLOPT_POST, TRUE );
				curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query ( $data ) );
				curl_setopt ( $ch, CURLOPT_HTTPHEADER, [
					'Content-Type: application/x-www-form-urlencoded',
				] );
				curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
				curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
				curl_setopt ( $ch, CURLOPT_SSL_VERIFYSTATUS, FALSE );
				$cookieFile = fopen ( "cookie.txt", "w+" );
				curl_setopt ( $ch, CURLOPT_COOKIEJAR, $cookieFile );
				$response = curl_exec ( $ch );
				//			var_dump($response);
				if ( $response === FALSE ) {
					$error = 500;
					curl_close ( $ch );
					$resp = [ 'error' => 500, 'error_description' => 'SAPLocalTransport' ];
					$response = json_encode ( $resp );
				}
				curl_setopt_array ( $ch, [
					CURLOPT_URL => 'http://www.banxico.org.mx/cep/descarga.do?formato=PDF',
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => TRUE,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => 'GET',
					CURLOPT_COOKIEFILE => $cookieFile,
				] );
				set_time_limit ( 180 );
				$pdf_content = curl_exec ( $ch );
				if ( curl_errno ( $ch ) ) {
					header ( 'HTTP/1.1 500 Internal Server Error' );
					echo 'Error al descargar el PDF.';
					exit;
				}
				curl_close ( $ch );
				$filename = strtotime ( 'now' ) . $args[ 'criterio' ] . '.pdf';
				$ruta_destino = './boveda/CEP/' . $filename;
				file_put_contents ( $ruta_destino, $pdf_content );
				$tipoMIME = mime_content_type ( $ruta_destino );
				if ( $tipoMIME === 'application/pdf' ) {
					//				var_dump($filename);
					if ( file_exists ( "cookie.txt" ) ) {
						unlink ( "cookie.txt" );
					}
					array_map ( 'unlink', glob ( "Resource id #" ) );
					return $filename;
				} else {
					if ( $try <= 3 ) {
						$try++;
						//					var_dump('Reintentando '.$filename);
						//					var_dump( 'Intento '.$try);
						$this->DownloadCEP ( $args, $try, $env );
					}
					//				var_dump('No se logro descargar '.$filename);
					if ( file_exists ( $ruta_destino ) ) {
						unlink ( $ruta_destino );
					} else {
						echo "El archivo no existe o no es un archivo.";
					}
					if ( file_exists ( "cookie.txt" ) ) {
						unlink ( "cookie.txt" );
					}
					array_map ( 'unlink', glob ( "Resource id #" ) );
					return -1;
					
				}
			} else {
				$resp[ 'reason' ] = 'No se pudo inicializar cURL';
				$response = json_encode ( $resp );
			}
			return -2;
		}
	}
