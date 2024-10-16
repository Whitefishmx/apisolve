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
		 * Función para obtener la información de un usuario proporcionando por un token
		 *
		 * @param string $mail correo electrónico asociado a unn usuario
		 *
		 * @return array error o datos de usuario
		 * @throws Exception
		 */
		public function findUserByTokenAccess ( string $mail ): array {
			//Se declara el ambiente a utilizar
			$query = "SELECT t1.id, t1.email, t2.name, t2.last_name, t2.sure_name, t2.rfc, t2.curp, t3.net_salary, t3.plan, t1.first_login
FROM users t1
    INNER JOIN person t2 ON t1.id = t2.user_id
    LEFT JOIN employee t3 ON t3.person_id = t2.id
WHERE (t1.email = '$mail' and t1.active = 1)
   OR (t1.nickname = '$mail' and t1.active = 1)
   OR (t2.rfc = '$mail' AND t1.active = 1)";
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return [ TRUE, $res->getResultArray ()[ 0 ] ];
				}
				return [ FALSE, 'No se encontró usuario con el correo proporcionado' ];
			}
			return [ FALSE, 'Error con la conexión a la fuente de información' ];
		}
		public function validateAccess ( string $login, string $password, int $platform ): array {
			$query = "SELECT t1.id, t1.email, t2.name, t2.last_name, t2.sure_name, t2.rfc, t2.curp, t3.net_salary, t3.plan, t1.first_login
FROM users t1
    INNER JOIN person t2 ON t1.id = t2.user_id
    LEFT JOIN employee t3 ON t3.person_id = t2.id
WHERE (t1.nickname = '$login' AND t1.password = '$password')
   OR (t1.email = '$login' AND t1.password = '$password') ";
			if ( $platform === 5 ) {
				$query .= "OR (t2.curp = '$login' AND t1.password = '$password') ";
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
		public function getBankAccountsByUser ( int $user ): array {
			$query = "SELECT b.clabe, b.card, b.month, b.year, c.bnk_alias, c.bnk_nombre, c.magicAlias
FROM bank_accounts b
    INNER JOIN cat_bancos c ON c.id = b.bank_id
    INNER JOIN users u ON b.user_id = u.id
WHERE u.id = $user";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 22, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 22, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 22, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ), json_encode (
				$res->getResultArray ()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
	}