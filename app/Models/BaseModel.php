<?php
	
	namespace App\Models;
	
	use Config\Database;
	use CodeIgniter\Model;
	use AllowDynamicProperties;
	
	#[AllowDynamicProperties] class BaseModel extends Model {
		public function __construct () {
			parent::__construct ();
			$this->db = Database::connect ( 'default' );
		}
		/**
		 * Obtiene el siguiente ID que será insertado
		 *
		 * @param string $table Tabla de la que se quiere obtener el siguiente ID
		 *
		 * @return int|array Siguiente ID que será insertado
		 */
		public function getNexId ( string $table ): int|array {
			$query = "SELECT MAX(id) AS id FROM $table";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de '.$table ];
			}
			$res = $res->getResultArray ()[ 0 ][ 'id' ];
			return $res === NULL ? 1 : intval ( $res + 1 );
		}
		public function generateFolio ( int $functionId, string $table, int $user = NULL ): string {
			helper ( 'tetraoctal_helper' );
			$nextId = $this->getNexId ( $table );
			if ( $user != NULL ) {
				$data = [ $user, $functionId, $nextId, strtotime ( 'now' ) ];
			} else {
				$data = [ $functionId, $nextId, strtotime ( 'now' ) ];
			}
			return serialize32 ( $data );
		}
		/**
		 * Genera un número de referencia que no este activo para que se puedan realizar rastrear la operación a la que pertenece
		 *
		 * @param int $id Id de la operación a la que se le generara una referencia
		 *
		 * @return string Numero de referencia valido.
		 */
		public function NewReferenceNumber ( int $id ): string {
			helper ( 'tools_helper' );
			$query = "SELECT operation_number AS number FROM operations WHERE status IN ( 0, 1 )
        UNION SELECT reference_number AS number FROM conciliation_plus WHERE status IN ( 0, 1 )
       	UNION SELECT reference_number AS number FROM dispersions_plus WHERE status IN ( 0, 1 )
       	UNION SELECT reference_number AS number FROM advance_payroll WHERE status IN ( 0, 1 )";
			if ( !$res = $this->db->query ( $query ) ) {
				return MakeOperationNumber ( $id );
			}
			$ref = MakeOperationNumber ( $id );
			while ( in_array ( $ref, $res->getResultArray ()[ 0 ] ) ) {
				$ref = MakeOperationNumber ( $id );
			}
			return $ref;
		}
	}