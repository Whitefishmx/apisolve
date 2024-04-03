<?php
	
	namespace App\Models;
	
	use CodeIgniter\Model;
	use Exception;
	use function PHPUnit\Framework\returnArgument;
	
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
	tipo VARCHAR(1) NOT NULL COLLATE 'utf8mb4_spanish_ci',
	invoice_date VARCHAR(50) NOT NULL DEFAULT '' COLLATE 'utf8mb4_spanish_ci',
	total DECIMAL(10,2) NOT NULL,
	UNIQUE INDEX uuid (uuid) USING BTREE) COLLATE = 'utf8mb4_spanish_ci' ENGINE = InnoDB";
			$this->db->db_debug = FALSE;
			if ( $this->db->query ( $query ) ) {
				foreach ( $data as $key => $val ) {
					$inDate = strtotime ( $val[ 'fecha' ] );
					$query = "INSERT INTO apisolve_sandbox.invoices_$tmpName values ('{$val['emisor']['rfc']}', '{$val['receptor']['rfc']}', '{$val['uuid']}',
                                                    '{$val['tipo']}','$inDate', '{$val['monto']}')";
					$this->db->db_debug = FALSE;
					if ( !$this->db->query ( $query ) ) {
						throw new Exception( 'No se pudieron insertar los registros.' );
					}
				}
				$query = "SELECT t1.sender_rfc AS sender, t1.receiver_rfc AS receiver, w.W AS 'in', s.S AS 'out',
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
				$this->db->db_debug = FALSE;
				if ( $res = $this->db->query ( $query ) ) {
					$item = [];
					$res = $res->getResultArray ();
					$rfcCompanies = $this->getCompaniesRegisters ( $env );
					for ( $i = 0; $i < count ( $res ); $i++ ) {
//						echo '============ITEM======='."\r\n";
//						var_dump ($item);
//						echo "============RES$i========\r\n";
//						var_dump ($res[$i]);
						if ( count ( $item ) > 0 ) {
//							echo "============dentro de if========\r\n";
							$counter = 0;
							for ( $j = 0; $j < count ( $item ); $j++ ) {
								if ( ( $res[ $i ][ 'sender' ] === $item[ $j ][ 'sender' ] || $res[ $i ][ 'sender' ] === $item[ $j ][ 'receiver' ] ) &&
									( $res[ $i ][ 'receiver' ] === $item[ $j ][ 'sender' ] || $res[ $i ][ 'receiver' ] === $item[ $j ][ 'receiver' ] ) ) {
									$counter++;
								}
							}
							if ( $counter === 0 ) {
								$res[ $i ][ 'tmp' ] = "invoices_$tmpName";
								$item [] = $res[ $i ];
							}
						} else {
							$res[ $i ][ 'tmp' ] = "invoices_$tmpName";
							$item [] = $res[ $i ];
						}
					}
//					echo "============final========\r\n";
//					var_dump ($item);
//					die();
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
//					$query = "DROP TABLE $this->base.invoices_$tmpName";
//					if ( !$this->db->query ( $query ) ) {
//						$conciliaciones[ 'errors' ] = 'tabla temporal persiste';
//					}
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
		/**
		 * Función para guardar los CFDI que si serán usados para conciliaciones masivas
		 * @param array       $data RFC de las empresas e información de balance de las conciliaciones
		 * @param string|NULL $env Ambiente en el que trabajara la BD LIVE|SANDBOX
		 *
		 * @return array Arreglo con los datos de las conciliaciones y los ID de los CFDI que se utilizaran
		 * @throws Exception Errores que pudieran ocurrir en el proceso
		 */
		public function savePermanentCfdi ( array $data, string $env = NULL ): array {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$ids = [];
			$counter = 0;
			foreach ( $data as $row ) {
				$query = "INSERT INTO apisolve_sandbox.cfdi_plus (sender_rfc, receiver_rfc, uuid, tipo, invoice_date, total)
    (SELECT * FROM $row[2] WHERE (sender_rfc = '$row[0]' AND receiver_rfc = '$row[1]') OR (sender_rfc = '$row[1]' AND receiver_rfc = '$row[0]'))";
				$this->db->db_debug = FALSE;
				if ( $this->db->query ( $query ) ) {
					$data[ $counter ][ 'insertedId' ] = $this->db->insertID ();
					$data[ $counter ][ 'affected' ] = $this->db->affectedRows ();
//					$portein = 'Bueno justo llegue a esa conclución del barco de teseo tambien, ammmm puse lo de las religiones por poner un ejemplo y en este caso hay
//					una variable muy marcada que seria justo la información de esa religion, desde un punto de vista no religioso, que hace a una religion esa religion
//					para empezar sus dioses, pero de ahi, los ritos, deben ser siempre los mismos? por que? si ahorita llega Zeus y me dice para mostrarme adoración tienes
//					que darte un toque eléctrico todos los Jueves, por que pues es la nueva era y estas las maquinitas de toques, o solo la posibilidad de cambiar un rito
//					lo tiene un sacerdote, o padre o chaman de alto rango, que tal que a ellos nunca se les presento una señal y un tipo cualquiera si y es real, bueno ahi
//					seria mucho mistisismo creo, pero justamente si lo que creemos de las religiones que no tenemos casi nada de info esta mal y obviamente sus intentos de
//					revivirlos no serian "adecuados" y no habria alguien que realmente pueda decir si es asi aunque tenga un codice perdido, por que a lo mejor ese dios
//					ni si quiera lo queria asi, en otras cuestiones como una obra literaria aqui hay un autor que si invento una historia (hablando de una novela o cuento)
//					y todo lo demas es una representacion de ello salvo que existiera "Moby Dick" y otro libro  ';
				}
				$counter++;
			}
			if ( count ( $data ) > 0 ) {
				$query = "DROP TABLE $this->base.invoices_$data[$counter][2]";
				$this->db->db_debug = FALSE;
				if ( $this->db->query ( $query ) ) {
					return $data;
				}
				throw new Exception('2.2 No se logro guardar la información requerida para las conciliaciones.');
			}
			throw new Exception( '2.1 No se logro guardar la información requerida para las conciliaciones.' );
		}
	}
