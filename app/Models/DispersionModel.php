<?php
	
	namespace App\Models;
	
	use CodeIgniter\Model;
	use Config\Database;
	
	class DispersionModel extends Model {
		protected $db;
		private string $environment = 'SANDBOX';
		private string $APISandbox = '';
		private string $APILive = '';
		public string $base = '';
		public function __construct () {
			parent::__construct ();
			require 'conf.php';
			$this->base = $this->environment === 'SANDBOX' ? $this->dbsandbox : $this->dbprod;
			$this->db = \Config\Database::connect ( 'default' );
		}
		/**
		 * Crea una dispersion masiva en el proceso de conciliación_plus (Conciliaciones seleccionadas por el usuario)
		 * @param array       $idConciliations Conciliaciones seleccionadas
		 * @param array       $user Usuario que está realizando la acción
		 * @param array       $company Compañía del usuario que está haciendo el proceso
		 * @param string|NULL $env Ambiente en el que se va a trabajar
		 *
		 * @return array Arreglo con los ID de la dispersion creada
		 */
		public function createDispersionCP ( array $idConciliations, array $user, array $company, string $env = NULL ): array {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			helper ( 'tools_helper' );
			helper ( 'tetraoctal_helper' );
			$nexID = $this->getNexId ( 'conciliation_plus' );
			if ( !is_numeric  ($nexID) ) {
				return [ FALSE,$nexID[ 1 ] ];
			}
			$referenceN = $this->NewReferenceNumber ( $nexID, $env );
			$data = [ 3, $nexID, $user[ 'id' ], $company[ 'id' ], strtotime ( 'now' ) ];
			$folio = serialize32 ( $data );
			$before = 0;
			$conciliacionesP = $this->getConciliacionesPInfo ( $idConciliations, $env );
			if ( !$conciliacionesP[ 0 ] ) {
				return [ FALSE, $conciliacionesP[ 1 ] ];
			}
			$needed = '';
			$detail = [];
			foreach ( $conciliacionesP as $row ) {
				if ( $row[ 'id_provider' ] === $company[ 'id' ] ) {
					$needed = floatval ( $needed ) + floatval ( $row[ 'exit_money' ] );
					$detail[] = "INSERT INTO $this->base.dispersions_plus_detail (id_created_by, folio_dispersion, reference_number, amount, account_clabe, bank)
VALUES ('{$user['id']}', '$folio', '{$row['reference_number']}', '{$row[ 'exit_money' ]}', '{$row['provider_clabe']}', '723')";
				} else {
					$needed = floatval ( $needed ) + floatval ( $row[ 'entry_money' ] );
					$detail[] = "INSERT INTO $this->base.dispersions_plus_detail (id_created_by, folio_dispersion, reference_number, amount, account_clabe, bank)
VALUES ('{$user['id']}', '$folio', '{$row['reference_number']}', '{$row[ 'entry_money' ]}', '{$row['client_clabe']}', '723')";
				}
			}
			$query = "INSERT INTO $this->base.dispersions_plus (id_created_by, id_company, reference_number, folio, balance_before, balance_needed, status)
VALUES ('{$user['id']}', '{$company['id']}','$referenceN', '$folio', '$before', '$needed', 1)";
			if ( !$this->db->query ( $query ) ) {
				return [ FALSE, '1.4 No se logro crear la Dispersion masiva' ];
			}
			$idsC = implode ( ",", $idConciliations );
			$query = "UPDATE $this->base.conciliation_plus SET folio_dispersion = '$folio' WHERE id IN ($idsC) AND status = 1";
			if ( $this->db->query ( $query ) ) {
				$detailId=[];
				foreach ($detail as $row){
					if ( !$this->db->query ( $row ) ) {
						return [ FALSE, '1.6 No se logro vincular las conciliaciones con la dispersion'  ];
					}
					$detailId[] = $this->db->insertID ();
				}
				return $detailId;
			}else{
				return [ FALSE, '1.5 No se logro vincular las conciliaciones con la dispersion' ];
			}
		}
		/**
		 * Obtiene el siguiente ID que será insertado
		 *
		 * @param string      $table Tabla de la que se quiere obtener el siguiente ID
		 * @param string|null $env   Ambiente en el que se va a trabajar
		 *
		 * @return int|array Siguiente ID que será insertado
		 * @noinspection DuplicatedCode
		 */
		public function getNexId ( string $table, string $env = NULL ): int|array {
			$this->environment = ( $env === NULL ) ? $this->environment : $env;
			$this->base = ( strtoupper ( $this->environment ) === 'SANDBOX' ) ? $this->APISandbox : $this->APILive;
			$query = "SELECT MAX(id) AS id FROM $this->base.$table";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE,  'No se encontró información de ' . $table];
			}
			$res = $res->getResultArray ()[ 0 ][ 'id' ];
			return $res === NULL ? 1 : intval ( $res + 1 );
		}
		/**
		 * Genera un número de referencia que no este activo para que se puedan realizar rastrear la operación a la que pertenece
		 *
		 * @param int         $id  Id de la operación a la que se le generara una referencia
		 * @param string|NULL $env Ambiente en el que se va a trabajar
		 *
		 * @return string Numero de referencia valido.
		 */
		public function NewReferenceNumber ( int $id, string $env = NULL ): string {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			helper ( 'tools_helper' );
			$query = "SELECT operation_number AS number FROM $this->base.operations WHERE status IN (0, 1)
UNION
SELECT reference_number AS number FROM $this->base.conciliation_plus WHERE status IN (0, 1)
UNION
SELECT reference_number AS number FROM $this->base.dispersions_plus WHERE status IN (0, 1)";
			if ( !$res = $this->db->query ( $query ) ) {
				
				return MakeOperationNumber ( $id );
			}
			$ref = MakeOperationNumber ( $id );
			while ( in_array ( $ref, $res->getResultArray ()[ 0 ] ) ) {
				$ref = MakeOperationNumber ( $id );
			}
			return $ref;
		}
		/**
		 * Obtiene la información de las conciliaciones que se solicitan por ID
		 *
		 * @param array       $idConciliations Id de las conciliaciones a buscar
		 * @param string|NULL $env             Ambiente en el que se va a trabajar
		 *
		 * @return array Información obtenida
		 */
		public function getConciliacionesPInfo ( array $idConciliations, string $env = NULL ): array {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$ids = implode ( ",", $idConciliations );
			$query = "SELECT t1.*, t2.arteria_clabe AS 'client_clabe', t3.arteria_clabe AS 'provider_clabe'
FROM $this->base.conciliation_plus t1
INNER JOIN $this->base.fintech t2 ON t1.id_client = t2.companie_id
INNER JOIN $this->base.fintech t3 ON t1.id_provider = t3.companie_id
WHERE t1.id IN ($ids) AND t1.status = 1";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, '1.3 No se logro asignar las conciliaciones a la dispersion' ];
			}
			return $res->getResultArray ();
		}
		/**
		 * Obtiene las Dispersiones Plus de una empresa
		 *
		 * @param mixed       $id  Id de la empresa a buscar dispersiones
		 * @param string|NULL $env ambiente en el que se trabajará
		 *
		 * @return array|array[] Arreglo con la información necesaria para mostrar las dispersiones
		 */
		public function getDispersionesPlus (string $from, string $to, int $company, string $env = NULL ): array {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "SELECT t1.status, t1.reference_number, t1.folio, t2.arteria_clabe AS 'clabe',
       t1.balance_needed, t1.balance_before, t1.balance_after,
       t1.created_at, t1.updated_at
FROM apisandbox_sandbox.dispersions_plus t1
    INNER JOIN apisandbox_sandbox.fintech t2 ON t2.companie_id = t1.id_company
WHERE t1.id_company = $company AND (t1.created_at >= '$from' AND t1.created_at <= '$to')";
			var_dump ($query);
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de dispersiones' ];
			}
			$res = $res->getResultArray ();
			if ( empty( $res ) ) {
				return [ FALSE, 'No se encontró información de las dispersiones' ];
			}
			return $res;
		}
	}