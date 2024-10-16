<?php
	
	namespace App\Models;
	header ( 'Content-Type: text/html; charset=utf-8' );
	
	class DataModel extends BaseModel {
		/**
		 * Obtiene el código postal de acuerdo a los argumentos ingresados (ciudad, estado)
		 *
		 * @param array       $args Argumentos de búsqueda
		 * @param string|NULL $env  Ambiente en el que se va a trabajar
		 *
		 * @return array|null  resultados
		 */
		public function getCPInfo ( array $args, string $env = NULL ): ?array {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "SELECT t1.* FROM $this->base.cat_zipcode t1
    LEFT JOIN cat_county t2 ON t1.zip_county = t2.cnty_id
    LEFT JOIN cat_state t3 ON t1.zip_state = t3.stt_id
            WHERE ";
			$query .= isset( $args[ 'cp' ] ) ? "t1.zip_code = '{$args[ 'cp' ]}' OR " : "";
			$query .= isset( $args[ 'county' ] ) ? "t2.cnty_name LIKE '%{$args[ 'county' ]}%' OR " : "";
			$query .= isset( $args[ 'state' ] ) ? "t3.stt_name LIKE '%{$args[ 'state' ]}%' OR " : "";
			$query = substr ( $query, 0, -4 );
			$query .= isset( $args[ 'limit' ] ) ? " limit {$args[ 'limit' ]}" : "";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de conciliaciones' ];
			}
			if ( $res->getNumRows () <= 0 ) {
				return NULL;
			}
			if ( intval ( $args[ 'limit' ] ) === 1 ) {
				return $res->getRowArray ();
			}
			return $res->getResultArray ();
		}
		/**
		 * Obtiene el regimen de acuerdo a la clave
		 *
		 * @param string      $clave Clave
		 * @param int|NULL    $limit Limite de resultados
		 * @param string|NULL $env   Ambiente en el que se va a trabajar
		 *
		 * @return array|null Resultados
		 */
		public function getRegimen ( string $clave, int $limit = NULL, string $env = NULL ): ?array {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "SELECT rg_regimen FROM $this->base.cat_regimenfiscal WHERE rg_clave = '$clave'";
			$query .= isset( $limit ) ? " limit $limit" : "";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de conciliaciones' ];
			}
			if ( $res->getNumRows () <= 0 ) {
				return NULL;
			}
			if ( intval ( $limit ) === 1 ) {
				return $res->getRowArray ();
			}
			return $res->getResultArray ();
		}
		/**
		 * Guarda un log en la base de datos
		 *
		 * @param array       $args Información a guardar
		 * @param string|NULL $env  Ambiente en el que se va a trabajar
		 *
		 * @return bool Respuesta si logro guardar
		 */
		public function saveLogs ( array $args ): bool {
			$query = "INSERT INTO logs ( id_user, task, code, data_in, result )
VALUES ( {$args['user']}, {$args['task']}, {$args['code']}, ";
			$query .= $args[ 'dataIn' ] === NULL ? " NULL, " : " '".$args[ 'dataIn' ]."', ";
			$query .= $args[ 'dataOut' ] === NULL ? " NULL ) " : " '".$args[ 'dataOut' ]."' ) ";
			/*		var_dump ($query);
					die();*/
			$this->db->query ( 'SET NAMES utf8mb4' );
			$this->db->query ( $query );
			if ( $this->db->affectedRows () === 0 ) {
				return FALSE;
			}
			return TRUE;
		}
		public function getBankByAccount ( string $clabe, int $user ): array {
			$clabe = substr ( $clabe, 0, 3 );
			$query = "SELECT c.* FROM cat_bancos c WHERE c.bnk_clave like '$clabe' ";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 21, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 20, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 20, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ), json_encode (
				$res->getResultArray ()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
	}