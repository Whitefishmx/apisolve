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
		public function makeConciliationPlus ( array $args, string $env = NULL ) {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			foreach ( $args as $row ) {
				$query = "INSERT INTO apisolve_sandbox.conciliation_plus (invoice_range, id_client, id_provider, reference_number, folio, entry_money, exit_money, payment_date, status, commentary, id_uploaded_by) VALUES ()";
			}
		}
		public function getClientProviderByRfc ( string $client, string $provider, string $env = NULL ) {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$companies = [];
			$query = "SELECT * FROM apisolve_sandbox.companies WHERE rfc = '$client'";
			
			$query = "SELECT * FROM apisolve_sandbox.companies WHERE rfc = '$provider'";
		}
	}
