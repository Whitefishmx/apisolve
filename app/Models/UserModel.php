<?php
	
	namespace App\Models;
	
	use CodeIgniter\Model;
	use Exception;
	use CodeIgniter\Database\ConnectionInterface;
	
	class UserModel extends Model {
		protected $db;
		private string $environment = '';
		private string $dbsandbox = '';
		private string $dbprod = '';
		private string $base = '';
		public function __construct () {
			parent::__construct ();
			require 'conf.php';
			$this->base = $this->environment === 'SANDBOX' ? $this->dbsandbox : $this->dbprod;
			$this->db = \Config\Database::connect ( 'default' );
		}
		/**
		 * Función para obtener la información de un usuario con acceso a token para validar el inicio de sesión.
		 *
		 * @param string      $user Username a buscar
		 * @param string|NULL $env  Ambiente en que se va a trabajar
		 *
		 * @return array|mixed error o datos de usuario
		 */
		public function authenticateToken ( string $user, string $env = NULL ): mixed {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->dbsandbox : $this->dbprod;
			$query = "SELECT * FROM $this->base.users WHERE user = '$user' and token = '1' and active = '1'";
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return $res->getResultArray ()[ 0 ];
				} else {
					throw new Exception( 'Credenciales incorrectas' );
				}
			}
			throw new Exception( 'Credenciales incorrectas' );
		}
		/**
		 * Función para obtener la información de un usuario proporcionando un correo electrónico
		 *
		 * @param string      $mail correo electrónico asociado a unn usuario
		 * @param string|NULL $env  Ambiente en que se va a trabajar
		 *
		 * @return array error o datos de usuario
		 * @throws Exception
		 */
		public function findUserByEmailAddress ( string $mail, string $env = NULL ): array {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->dbsandbox : $this->dbprod;
			$query = "SELECT * FROM $this->base.users WHERE email = '$mail' and active = 1";
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return $res->getResultArray ();
				}
				throw new Exception( 'No se encontró usuario con el correo proporcionado' );
			}
			throw new Exception( 'Error con la conexión a la fuente de información' );
		}
	}