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
    INNER JOIN person_user pu ON t1.id = pu.user_id
    INNER JOIN person t2 ON pu.person_id = t2.id
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
			$query = "SELECT t1.id, t1.email, t2.name, t2.last_name, t2.sure_name, t2.rfc, t2.curp, t3.net_salary, t3.plan, t1.first_login, t3.company_id
FROM users t1
    INNER JOIN person_user pu ON t1.id = pu.user_id
    INNER JOIN person t2 ON pu.person_id = t2.id
    LEFT JOIN employee t3 ON t3.person_id = t2.id
WHERE (t1.nickname = '$login' AND t1.password = '$password')
   OR (t1.email = '$login' AND t1.password = '$password') ";
			if ( $platform === 6 ) {
				$query .= "OR (t2.curp = '$login' AND t1.password = '$password') ";
			}
			$query .= "AND t1.active = 1 ";
//			var_dump ($query);
//			die();
			$res = $this->db->query ( $query );
//			var_dump($res->getNumRows ());
			if ( $res->getNumRows () === 0 ) {
				return [ FALSE, $res->getNumRows () ];
			}
			$res = $res->getResultArray ();
			$userid = $res[ 0 ][ 'id' ];
			$user = $res[ 0 ];
			$query = "SELECT v.name, v.session, v.route, p.writable
FROM permissions p
    JOIN views v ON p.view_id = v.id
    JOIN users u ON p.user_id = u.id
    JOIN platform_access pa ON pa.id_user = u.id AND pa.id_platform = v.platform_id
WHERE pa.id_platform = $platform AND u.id  = $userid";
//			var_dump ($query);
//			die();
			$res = $this->db->query ( $query );
			if ( $res->getNumRows () === 0 ) {
				return [ FALSE, $res->getNumRows () ];
			}
			$data = [ 'id' => $userid, 'permissions' => $res->getResultArray (), 'userData' => $user ];
			return [ TRUE, $data ];
		}
		public function getBankAccountsByUser ( int $user ): array {
			$query = "SELECT b.id, b.clabe, b.card, b.month, b.year, c.bnk_alias, c.bnk_nombre, c.magicAlias
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
		public function updatePassword ( int $user, string $password ): array {
			$query = "UPDATE users SET password = '$password' WHERE id = $user";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					saveLog ( $user, 32, 200, json_encode ( [ 'newPassword' => $password, ] ), json_encode
					( [ 'affected' => $affected ] ) );
					return [ TRUE, 'Se actualizó correctamente la contraseña' ];
				}
				saveLog ( $user, 32, 404, json_encode ( [ 'newPassword' => $password, ] ), json_encode ( [ FALSE, 'affected' => $affected ] ) );
				return [ FALSE, 'No se encontró registro a actualizar' ];
			}
			saveLog ( $user, 32, 500, json_encode ( [ 'newPassword' => $password, ] ), json_encode ( [ FALSE, 'affected' => $this->db->error () ] ) );
			return [ FALSE, 'No se pudo actualizar la contraseña' ];
		}
	}