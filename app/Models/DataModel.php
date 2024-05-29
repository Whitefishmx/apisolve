<?php
	
	namespace App\Models;
	class DataModel extends BaseModel {
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
	}