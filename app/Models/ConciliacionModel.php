<?php
	
	namespace App\Models;
	
	use CodeIgniter\Model;
	use Config\Database;
	use Exception;
	
	class ConciliacionModel extends Model {
		protected $db;
		private string $environment = '';
		private string $APISandbox = '';
		private string $APILive = '';
		public string $base = '';
		public function __construct () {
			parent::__construct ();
			require 'conf.php';
			$this->base = $this->environment === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$this->db = Database::connect ( 'default' );
		}
		/**
		 * Crea
		 *
		 * @param array       $args
		 * @param string|NULL $env
		 *
		 * @return array
		 * @throws Exception
		 */
		public function makeConciliationPlus ( array $args, string $env = NULL ): array {
			$user = json_decode ( base64_decode ( array_pop ( $args ) ), TRUE );
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$counter = 0;
			foreach ( $args as $row ) {
				helper ( 'tools_helper' );
				helper ( 'tetraoctal_helper' );
				$companies = $this->getClientProviderByRfc ( $row[ 0 ], $row[ 1 ], $env );
				$range = $row[ 'insertedId' ] + ( intval ( $row[ 'affected' ] ) - 1 );
				$nexID = $this->getNexId ( 'conciliation_plus' );
				$opNumber = MakeOperationNumber ( $nexID );
				$data = [ 2, $nexID, $user[ 'id' ], $companies[ 'client' ][ 'id' ], $companies[ 'provider' ][ 'id' ], strtotime ( 'now' ) ];
				$folio = serialize32 ( $data );
				$paymentDate = strtotime ( '+40 days' );
				$query = "INSERT INTO $this->base.conciliation_plus (invoice_range, id_client, id_provider, reference_number, folio, entry_money, exit_money, payment_date, status)
VALUES ('{$row['insertedId']}-$range', '{$companies['client']['id']}', '{$companies['provider']['id']}', '$opNumber', '$folio', '$row[3]', '$row[4]', '$paymentDate', 0)";
				if ( !$this->db->query ( $query ) ) {
					throw new Exception( 'No se lograron crear las conciliaciones' );
				}
				$args[ $counter ][ 'idConciliation' ] = $this->db->insertID ();
				$counter++;
			}
			return $args;
		}
		/**
		 * Obtiene la información de las empresas que estarán implicadas en las conciliaciones
		 *
		 * @param string      $client   RFC del "cliente"
		 * @param string      $provider RFC del "proveedor"
		 * @param string|NULL $env      Ambiente en el que se trabajara SANDBOX|LIVE
		 *
		 * @return array Arreglo con la información de ambas empresas
		 * @throws Exception Errores en la ejecución
		 */
		public function getClientProviderByRfc ( string $client, string $provider, string $env = NULL ): array {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$companies = [];
			$query = "SELECT * FROM apisolve_sandbox.companies WHERE rfc = '$client'";
			if ( !$res = $this->db->query ( $query ) ) {
				throw new Exception( 'No se encontró información de ' . $client );
			}
			$companies[ 'client' ] = $res->getResultArray ()[ 0 ];
			$query = "SELECT * FROM apisolve_sandbox.companies WHERE rfc = '$provider'";
			if ( !$res = $this->db->query ( $query ) ) {
				throw new Exception( 'No se encontró información de ' . $provider );
			}
			$companies[ 'provider' ] = $res->getResultArray ()[ 0 ];
			return $companies;
		}
		/**
		 * Obtiene el siguiente ID que será insertado
		 *
		 * @param string      $table Tabla de la que se quiere obtener el siguiente ID
		 * @param string|null $env   Ambiente en el que se va a trabajar
		 *
		 * @return int Siguiente ID que será insertado
		 * @throws Exception
		 */
		public function getNexId ( string $table, string $env = NULL ): int {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "SELECT MAX(id) AS id FROM apisolve_sandbox.$table";
			if ( !$res = $this->db->query ( $query ) ) {
				throw new Exception( 'No se encontró información de ' . $table );
			}
			$res = $res->getResultArray ()[ 0 ][ 'id' ];
			return $res === NULL ? 1 : intval ( $res + 1 );
		}
	}
