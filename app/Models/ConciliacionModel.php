<?php
	
	namespace App\Models;
	
	use CodeIgniter\Model;
	
	class ConciliacionModel extends Model {
		protected $db;
		private string $environment = '';
		private string $dbsandbox = '';
		private string $dbprod = '';
		public string $base = '';
		public function __construct () {
			parent::__construct ();
			require 'conf.php';
			$this->base = $this->environment === 'SANDBOX' ? $this->dbsandbox : $this->dbprod;
			$this->db = $db = \Config\Database::connect ( 'default' );
		}
	}
