<?php
	
	namespace App\Models;
	
	use Config\Database;
	use CodeIgniter\Model;
	use AllowDynamicProperties;
	use JetBrains\PhpStorm\NoReturn;
	
	#[AllowDynamicProperties] class BaseModel extends Model {
		public function __construct () {
			parent::__construct ();
			$this->db = Database::connect ( 'default' );
		}
		public function resultsNotFound ( int $error, string $description, string $reason ): void {
			$this->responseBody = [ 'error' => $this->errCode = $error, 'description' => $description, 'reason' => $reason ];
			echo json_encode ( $this->responseBody );
			http_response_code ( $error );
			exit;
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
		/**
		 * Generates a unique folio (identifier) based on the given parameters.
		 *
		 * This function creates a serialized string that can be used as a unique identifier
		 * for various operations in the system. It combines the function ID, next available ID
		 * from the specified table, current timestamp, and optionally a user ID.
		 *
		 * @param int    $functionId The ID of the function or operation type.
		 * @param string $table      The name of the table to get the next ID from.
		 * @param int    $user       (Optional) The ID of the user associated with this folio.
		 *
		 * @return string A serialized string representing the unique folio.
		 */
		public function generateFolio ( int $functionId, string $table, ?int $user = NULL ): string {
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
		/**
		 * Retrieves the employee ID associated with a given user ID.
		 *
		 * This function performs a database query to find the employee record
		 * linked to the specified user through various table joins.
		 *
		 * @param int $user The ID of the user to look up.
		 *
		 * @return array An array containing either:
		 *               - [0 => employee_id] if the employee is found
		 *               - [false, 'No se encontró información'] if no employee is found or an error occurs
		 */
		public function getEmployeeByIdUser ( int $user ): array {
			$query = "SELECT e.id FROM users u
INNER JOIN employee_user eu ON u.id  = eu.user_id
INNER JOIN employee e ON e.id = eu.employee_id
INNER JOIN person_user pu ON u.id = pu.user_id
INNER JOIN person p ON pu.person_id = p.id WHERE u.id = $user";
			//			var_dump ( $query);die();
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 20, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 20, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 20, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ), json_encode (
				$res->getResultArray ()[ 0 ], TRUE ) );
			return [ $res->getResultArray ()[ 0 ] ];
		}
		public function getBankByClave ( $clabe ) {
			$clabe = substr ( $clabe, 0, 3 );
			$query = "SELECT * FROM cat_bancos WHERE bnk_clave = '$clabe' ";
			//			var_dump ($query);die();
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return [ TRUE, $res->getResultArray ()[ 0 ] ];
				}
				return [ FALSE, [ 'error' => 'No existe' ] ];
			}
		}
	}