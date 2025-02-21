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
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 0 ) {
				if ( $res->getNumRows () >= 1 ) {
					$res = $res->getResultArray ();
					return [ TRUE, $res ];
				}
			}
			return [ FALSE, 'No se encontraron resultados' ];
		}
		public function DownloadCEP ( array $args, int $try ) {
			$tmp_dir = './public/boveda/CEP/tmp/';
			if ( !is_dir ( $tmp_dir ) ) {
				mkdir ( $tmp_dir, 0755, TRUE );
			}
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
				$cookieFile = fopen ( $tmp_dir."cookie.txt", "w+" );
				curl_setopt ( $ch, CURLOPT_COOKIEJAR, $cookieFile );
				$response = curl_exec ( $ch );
				//				var_dump ( $response );
				//				die();
				if ( $response === FALSE ) {
					curl_close ( $ch );
					$this->cleanupTmpFiles ( $tmp_dir );
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
					$this->cleanupTmpFiles ( $tmp_dir );
					exit;
				}
				curl_close ( $ch );
				$filename = strtotime ( 'now' ).'_'.$args[ 'description' ].'.pdf';
				$ruta_destino = './public/boveda/CEP/'.$filename;
				file_put_contents ( $ruta_destino, $pdf_content );
				$tipoMIME = mime_content_type ( $ruta_destino );
				if ( $tipoMIME === 'application/pdf' ) {
					$this->cleanupTmpFiles ( $tmp_dir );
					curl_close ( $ch );
					echo "Descargado".PHP_EOL;
					return $filename;
				} else {
					if ( $try <= 3 ) {
						echo "Intento $try de 3".PHP_EOL;
						$try++;
						curl_close ( $ch );
						$this->DownloadCEP ( $args, $try );
					}
					if ( file_exists ( $ruta_destino ) ) {
						curl_close ( $ch );
						unlink ( $ruta_destino );
					}
					$this->cleanupTmpFiles ( $tmp_dir ); // Limpiar archivos temporales
					curl_close ( $ch );
					echo "Fallo descarga".PHP_EOL;
					return -1;
				}
			}
			curl_close ( $ch );
			$this->cleanupTmpFiles ( $tmp_dir );
			echo "Fallo descarga".PHP_EOL;
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
		private function cleanupTmpFiles ( string $tmp_dir ): void {
			$files = glob ( $tmp_dir.'*' ); // Obtener todos los archivos en el directorio tmp
			foreach ( $files as $file ) {
				if ( is_file ( $file ) ) {
					unlink ( $file ); // Eliminar el archivo
				}
			}
			$files = glob ( "Resource id #*" );
			foreach ( $files as $file ) {
				if ( is_file ( $file ) ) { // Verificar que sea un archivo
					unlink ( $file );      // Eliminar el archivo
				}
			}
		}
		public function verifyMagicTransactions ( mixed $input ): bool {
			if ( !$data = $this->db->query ( "SELECT * FROM transactions t WHERE t.external_id LIKE '%{$input['transferId']}%'" ) ) {
				return FALSE;
			}
			$data = $data->getRowArray ();
			if ( intval ( $data[ 'op_type' ] ) === 1 && $input[ 'status' ] === 'paid' ) {
				$this->db->query ( "UPDATE bank_accounts ba SET ba.validated = 1, ba.active = 1 WHERE id = '{$data['account_destination']}'" );
			}
			if ( !$this->db->query ( "UPDATE transactions t SET t.status = '{$input[ 'status' ]}' WHERE id = '{$data['id']}'" ) ) {
				return FALSE;
			}
			return TRUE;
		}
		public function getInsertAccount ( mixed $clabe, mixed $bancoBeneficiario ): false|array|int|string {
			$query = "SELECT * FROM bank_accounts WHERE clabe = '$clabe' AND active = 1";
//			echo $query;
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
//					var_dump ($res->getRowArray ()[ 'id' ] );
					return [ $res->getRowArray ()[ 'id' ] ];
				}
				$query = "INSERT INTO bank_accounts ( external, bank_id, clabe, active, validated) VALUES (1, '{$bancoBeneficiario['id']}', '$clabe', 1, 1 )";
				if ( $this->db->query ( $query ) ) {
					return $this->db->insertID ();
				}
			}
			return FALSE;
		}
	}