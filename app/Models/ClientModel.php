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
		public function findClientById ( $id ): object|array {
			$client = $this->asArray ()->where ( [ 'id' => $id ] )->first ();
			if ( !$client ) {
				return [ FALSE, 'No se encuentran clientes para el ID especifico' ];
			}
			return $client;
		}
		public function getClientByArgs ( $args ): mixed {
			$query = "SELECT * FROM $this->base.client where name like '%$args%' OR retainer_fee LIKE '%$args%'";
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return $res->getResultArray ()[ 0 ];
				} else {
					return [ FALSE, 'Credenciales incorrectas' ];
				}
			}
			return [ FALSE, 'Credenciales incorrectas' ];
		}
	}