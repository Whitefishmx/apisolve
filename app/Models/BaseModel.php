<?php
	
	namespace App\Models;
	
	use Config\Database;
	use CodeIgniter\Model;
	
	class BaseModel extends Model {
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
			helper ('tetraoctal_helper');
			$nextId = $this->getNexId ($table);
			$data = [ $functionId, $nextId, strtotime ( 'now' ) ];
			return $folio = serialize32 ( $data );
		}
	}