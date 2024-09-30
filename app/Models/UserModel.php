<?php
	
	namespace App\Models;
	
	use Exception;
	
	class UserModel extends BaseModel {
		/**
		 * Función para obtener la información de un usuario con acceso a token para validar el inicio de sesión.
		 *
		 * @param string $user Username a buscar
		 *
		 * @return array|mixed error o datos de usuario
		 * @throws Exception
		 */
		public function authenticateToken ( string $user ): mixed {
			$query = "SELECT * FROM users WHERE nickname = '$user' and token = '1' and active = '1'";
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
		 * @param string $mail correo electrónico asociado a unn usuario
		 *
		 * @return array error o datos de usuario
		 * @throws Exception
		 */
		public function findUserByTokenAccess ( string $mail ): array {
			//Se declara el ambiente a utilizar
			$query = "SELECT t1.id, t1.email, t2.name, t2.last_name, t2.sure_name, t2.rfc, t2.curp, t3.net_salary, t3.plan
FROM users t1
    INNER JOIN person t2 ON t1.id = t2.user_id
    LEFT JOIN employee t3 ON t3.person_id = t2.id
WHERE (t1.email = '$mail' and t1.active = 1)
   OR (t1.nickname = '$mail' and t1.active = 1)
   OR (t2.rfc = '$mail' AND t1.active = 1)";
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return [ TRUE, $res->getResultArray ()[0] ];
				}
				return [ FALSE, 'No se encontró usuario con el correo proporcionado' ];
			}
			return [ FALSE, 'Error con la conexión a la fuente de información' ];
		}
		public function validateAccess ( string $login, string $password, int $platform ): array {
			$query = "SELECT t1.id, t1.email, t2.name, t2.last_name, t2.sure_name, t2.rfc, t2.curp, t3.net_salary, t3.plan
FROM users t1
    INNER JOIN person t2 ON t1.id = t2.user_id
    LEFT JOIN employee t3 ON t3.person_id = t2.id
WHERE (t1.nickname = '$login' AND t1.password = '$password')
   OR (t1.email = '$login' AND t1.password = '$password') ";
			if ( $platform === 5 ) {
				$query .= "OR (t2.rfc = '$login' AND t1.password = '$password') ";
			}
			$query .= "AND t1.active = 1 ";
			$res = $this->db->query ( $query );
			if ( $res->getNumRows () === 0 ) {
				return [ FALSE, $res->getNumRows () ];
			}
			$res = $res->getResultArray ();
			$userid = $res[ 0 ][ 'id' ];
			$user = $res[ 0 ];
			$query = "SELECT t4.name, t4.session, t4.route, t3.writable
FROM users t1
    INNER JOIN platform_access t2 ON t1.id  = t2.id_user AND t2.id_platform = $platform
    INNER JOIN permissions t3 ON t3.user_id = t1.id
    INNER JOIN views t4 ON t4.id = t3.view_id
WHERE t2.id_platform = $platform AND t1.id  = $userid";
			$res = $this->db->query ( $query );
			if ( $res->getNumRows () === 0 ) {
				return [ FALSE, $res->getNumRows () ];
			}
			$data = [ 'id' => $userid, 'permissions' => $res->getResultArray (), 'userData' => $user ];
			return [ TRUE, $data ];
		}
	}