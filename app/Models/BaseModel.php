<?php
	
	namespace App\Models;
	
	use CodeIgniter\Model;
	
	class BaseModel extends Model {
		public $db;
		public string $environment = 'SANDBOX';
		public string $APISandbox = '';
		public string $APILive = '';
		public string $base = '';
		public function __construct () {
			parent::__construct ();
			require 'conf.php';
			$this->base = $this->environment === 'SANDBOX' ? $this->dbsandbox : $this->dbprod;
			$this->db = \Config\Database::connect ( 'default' );
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
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "SELECT MAX(id) AS id FROM $this->base.$table";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de ' . $table ];
			}
			$res = $res->getResultArray ()[ 0 ][ 'id' ];
			return $res === NULL ? 1 : intval ( $res + 1 );
		}
	}