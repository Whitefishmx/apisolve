<?php
	
	namespace App\Models;
	
	use CodeIgniter\Model;
	use Exception;
	
	class CfdiModel extends Model {
		protected $db;
		private string $environment = '';
		private string $APISandbox = '';
		private string $APILive = '';
		public string $base = '';
		public function __construct () {
			parent::__construct ();
			require 'conf.php';
			$this->base = $this->environment === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$this->db = $db = \Config\Database::connect ( 'default' );
		}
		/**
		 * Función para poder obtener los grupos de conciliaciones posibles
		 *
		 * @param array       $data Arreglo de documentos para generar conciliaciones
		 * @param string|NULL $env  Entorno en el que se va a trabajar
		 *
		 * @return array $res Arreglo con las conciliaciones que se pueden crear
		 * @throws Exception Error que se genera
		 */
		public function createTmpInvoices ( array $data, string $env = NULL ): array {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$tmpName = md5 ( rand () );
			$query = "CREATE TABLE $this->base.invoices_$tmpName (
    sender_rfc VARCHAR(20) NOT NULL COLLATE 'utf8mb4_spanish_ci',
	receiver_rfc VARCHAR(20) NOT NULL COLLATE 'utf8mb4_spanish_ci',
	uuid VARCHAR(36) NOT NULL COLLATE 'utf8mb4_spanish_ci',
	invoice_date VARCHAR(50) NOT NULL DEFAULT '' COLLATE 'utf8mb4_spanish_ci',
	total DECIMAL(10,2) NOT NULL,
	UNIQUE INDEX uuid (uuid) USING BTREE) COLLATE = 'utf8mb4_spanish_ci' ENGINE = InnoDB AUTO_INCREMENT = 2";
			$this->db->db_debug = FALSE;
			if ( $this->db->query ( $query ) ) {
				foreach ( $data as $key => $val ) {
					$inDate = strtotime ( $val[ 'fecha' ] );
					$query = "INSERT INTO apisolve_sandbox.invoices_$tmpName values ('{$val['emisor']['rfc']}', '{$val['receptor']['rfc']}', '{$val['uuid']}',
                                                    '$inDate', '{$val['monto']}')";
					$this->db->db_debug = FALSE;
					if ( !$this->db->query ( $query ) ) {
						throw new Exception( 'No se pudieron insertar los registros.' );
					}
				}
				$query = "SELECT t1.sender_rfc AS sender, t1.receiver_rfc AS receiver, w.W, s.S,
       (IF(w.W > s.S, w.W - s.S, s.S - w.W)) AS difference,
       (IF(w.W > s.S, 'Favor', 'Contra')) AS saldo
FROM (SELECT sender_rfc, receiver_rfc
      FROM $this->base.invoices_$tmpName
      GROUP BY sender_rfc, receiver_rfc) AS t1
    LEFT JOIN (SELECT sender_rfc, receiver_rfc, SUM(total) AS W
               FROM $this->base.invoices_$tmpName
               GROUP BY sender_rfc, receiver_rfc) AS w
        ON t1.sender_rfc = w.sender_rfc AND t1.receiver_rfc = w.receiver_rfc
    LEFT JOIN (SELECT sender_rfc, receiver_rfc, SUM(total) AS S
               FROM $this->base.invoices_$tmpName
               GROUP BY sender_rfc, receiver_rfc) AS s
        ON t1.sender_rfc = s.receiver_rfc AND t1.receiver_rfc = s.sender_rfc";
//				var_dump ( $query );
				$this->db->db_debug = FALSE;
				if ( $res = $this->db->query ( $query ) ) {
					$item = [];
					$res = $res->getResultArray ();
					$rfcCompanies = $this->getCompaniesRegisters ( $env );
//					var_dump ( $rfcCompanies );
					for ( $i = 0; $i < count ( $res ); $i++ ) {
						if ( count ( $item ) > 0 ) {
							$counter = 0;
							for ( $j = 0; $j < count ( $item ); $j++ ) {
								if ( ( $res[ $i ][ 'sender' ] === $item[ $j ][ 'sender' ] || $res[ $i ][ 'sender' ] === $item[ $j ][ 'receiver' ] ) &&
									( $res[ $i ][ 'receiver' ] === $item[ $j ][ 'sender' ] || $res[ $i ][ 'receiver' ] === $item[ $j ][ 'receiver' ] ) ) {
									$counter++;
								}
							}
							if ( $counter === 0 ) {
								$item[] = $res[ $i ];
							}
						} else {
							$item [] = $res[ $i ];
						}
					}
					$finalItem = [];
					$finalItemErr = [];
					for ( $i = 0; $i < count ( $item ); $i++ ) {
						$counter = 0;
						for ( $j = 0; $j < count ( $rfcCompanies ); $j++ ) {
							if ( $item[ $i ][ 'sender' ] === $rfcCompanies[ $j ][ 'rfc' ] || $item[ $i ][ 'receiver' ] === $rfcCompanies[ $j ][ 'rfc' ] ) {
								$counter++;
							}
						}
						if ( $counter >= 2 ) {
							$finalItem[] = $item[ $i ];
						} else {
							$finalItemErr[] = $item[ $i ];
						}
					}
					$conciliaciones  [ 'conciliaciones' ] = $finalItem;
					if ( !empty( $finalItemErr ) ) {
						$conciliaciones  [ 'errors' ] = $finalItemErr;
					}
					$query = "DROP TABLE $this->base.invoices_$tmpName";
					if ( !$this->db->query ( $query ) ) {
						$conciliaciones[ 'errors' ] = 'tabla temporal persiste';
					}
					return $conciliaciones;
				}
				throw new Exception( 'No se lograron formar los grupos de conciliaciones' );
			}
			throw new Exception( 'No se logro iniciar el proceso para generar una conciliación masiva.' );
		}
		/**
		 * @param string|NULL $env
		 *
		 * @return array
		 * @throws Exception
		 */
		public function getCompaniesRegisters ( string $env = NULL ): array {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "SELECT rfc FROM $this->base.companies WHERE active = 1";
			$this->db->db_debug = FALSE;
			if ( $res = $this->db->query ( $query ) ) {
				return $res->getResultArray ();
			}
			throw new Exception( 'No se lograron obtener resultados' );
		}
	}
