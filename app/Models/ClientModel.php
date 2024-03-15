<?php
	
	namespace App\Models;
	
	use CodeIgniter\Model;
	
	class ClientModel extends Model {
		protected $table = 'client';
		protected $allowedFields = [
			'name', 'email', 'retainer_fee',
		];
		protected $useTimestamps = TRUE;
		protected $updatedField = 'updated_at';
		public function findClientById ( $id ) {
			$client = $this->asArray ()->where ( [ 'id' => $id ] )->first ();
			if ( !$client ) {
				throw new \Exception( 'Could not find client for specified ID' );
			}
			return $client;
		}
		public function getClientByArgs ( $args ) {
			$query = "SELECT * FROM apisolve_sandbox.client where name like '%$args%' OR retainer_fee LIKE '%$args%'";
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return $res->getResultArray ()[ 0 ];
				} else {
					throw new Exception( 'Credenciales incorrectas' );
				}
			}
			throw new Exception( 'Credenciales incorrectas' );
		}
	}