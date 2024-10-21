<?php
	
	namespace App\Models;
	class TransactionsModel extends BaseModel {
		/** @noinspection SqlInsertValues */
		public function insertTransaction ( $table, $args, $user ): array {
			$query = "INSERT INTO transactions ( $table, external_id, description, noReference, amount, account_destination, account_origin, status )
VALUES ('{$args['opId']}', '{$args['transactionId']}', '{$args['description']}', '{$args['noReference']}', '{$args['amount']}', '{$args['destination']}',
        '{$args['origin']}', 'process')";
			//			var_dump ($query);
			//			die();
			$this->db->query ( 'SET NAMES utf8mb4' );
			if ( $this->db->query ( $query ) ) {
				$id = $this->db->insertId ();
				saveLog ( $user, 23, 200, json_encode ( $args ), json_encode ( [ 'id' => $id ], TRUE ) );
				$dataOut = [ 'OperationId' => $args[ 'opId' ], 'TransactionId' => $id ];
				return [ TRUE, $dataOut ];
			} else {
				saveLog ( $user, 23, 400, json_encode ( $args ), json_encode ( [ FALSE, 'No se pudo generar el pedido' ] ) );
				return [ FALSE, 'No se pudo generar el pedido' ];
			}
		}
		public function updateTransactionStatus ( $folio, $noRef, $status, $user ): array {
			$query = "UPDATE transactions SET status = '$status' WHERE external_id like '%$folio%' AND noReference = '$noRef'";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					saveLog ( $user, 26, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
					( [ 'affected' => $affected ] ) );
					return [ TRUE, 'Se actualizó el estado de las transacciones' ];
				}
				saveLog ( $user, 26, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
				( [ FALSE, 'affected' => $affected ] ) );
				return [ FALSE, 'No se encontró registro a actualizar' ];
			}
			saveLog ( $user, 26, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
			( [ FALSE, 'affected' => $this->db->error () ] ) );
			return [ FALSE, 'No se pudo actualizar el estado de las transacciones' ];
		}
		public function getDataForCep (): array {
			$query = "SELECT t.id, t.external_id, t.description, t.noReference, t.amount, t.created_at as 'transaction_date',
       b1.clabe as 'clabe_origin', cb1.bnk_code as 'codigo_origen', cb1.bnk_alias as 'alias_origen',
       b2.clabe as 'clabe_destino', cb2.bnk_code as 'codigo_destino', cb2.bnk_alias as 'alias_destino'
FROM transactions t
    INNER JOIN bank_accounts b1 ON b1.id  = t.account_origin
    INNER JOIN cat_bancos cb1 ON cb1.id = b1.bank_id
    INNER JOIN bank_accounts b2 ON b2.id  = t.account_destination
    INNER JOIN cat_bancos cb2 ON cb2.id = b2.bank_id
WHERE t.cep  IS NULL";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( 2, 28, 404, json_encode ( [] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 0 ) {
				if ( $res->getNumRows () === 1 ) {
					$res = $res->getResultArray ();
					saveLog ( 2, 28, 200, json_encode ( [] ),  json_encode ( $res ) );
					return [ TRUE, $res ];
				}
				$res = $res->getResultArray ();
				saveLog ( 2, 28, 200, json_encode ( [] ), json_encode ( $res ) );
				return [ TRUE, $res ];
			} else {
				saveLog ( 2, 28, 404, json_encode ( [] ), json_encode
				( [ 'res' => 'No se encontraron resultados' ] ) );
				return [ FALSE, 'No se encontraron resultados' ];
			}
		}
		public function DownloadCEP ( array $args, int $try ) {
			sleep ( rand ( 5, 10 ) );
			$data = [
				'tipoCriterio'         => 'R',
				'fecha'                => date ( 'd-m-Y', strtotime ( $args[ 'transaction_date' ] ) ),
				'criterio'             => $args[ 'noReference' ],
				'emisor'               => $args[ 'codigo_origen' ],
				'receptor'             => $args[ 'codigo_destino' ],
				'cuenta'               => $args[ 'clabe_destino' ],
				'monto'                => $args[ 'amount' ],
				'receptorParticipante' => 0,
				'captcha'              => 'c',
				'tipoConsulta'         => 1,
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
				//				var_dump ( $response );
				//				die();
				if ( $response === FALSE ) {
					curl_close ( $ch );
					return -1;
				}
				curl_setopt_array ( $ch, [
					CURLOPT_URL            => 'https://www.banxico.org.mx/cep/descarga.do?formato=PDF',
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_ENCODING       => '',
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_TIMEOUT        => 0,
					CURLOPT_FOLLOWLOCATION => TRUE,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => 'GET',
					CURLOPT_COOKIEFILE     => $cookieFile,
				] );
				set_time_limit ( 180 );
				$pdf_content = curl_exec ( $ch );
				if ( curl_errno ( $ch ) ) {
					header ( 'HTTP/1.1 500 Internal Server Error' );
					echo 'Error al descargar el PDF.';
					exit;
				}
				curl_close ( $ch );
				$filename = strtotime ( 'now' ).'_'.$args[ 'description' ].'.pdf';
				$ruta_destino = './public/boveda/CEP/'.$filename;
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
						$this->DownloadCEP ( $args, $try );
					}
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
			}
			return -2;
		}
		public function insertCep ( array $data ): array {
			$query = "UPDATE transactions SET cep = '{$data['filename']}' WHERE id = '{$data['idTransaction']}' ";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					saveLog ( 2, 30, 200, json_encode ( $data ), json_encode
					( [ 'affected' => $affected ] ) );
					return [ TRUE, 'Se actualizó el estado de las transacciones' ];
				}
				saveLog ( 2, 30, 200, json_encode ( $data ), json_encode
				( [ FALSE, 'affected' => $affected ] ) );
				return [ FALSE, 'No se encontró registro a actualizar' ];
			}
			saveLog ( 2, 30, 200, json_encode ( $data ), json_encode
			( [ FALSE, 'affected' => $this->db->error () ] ) );
			return [ FALSE, 'No se pudo actualizar el estado de las transacciones' ];
		}
	}
