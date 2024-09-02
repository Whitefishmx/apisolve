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
		public function findUserByEmailAddress ( string $mail): array {
			//Se declara el ambiente a utilizar
			$query = "SELECT * FROM users WHERE email = '$mail' and active = 1";
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return $res->getResultArray ();
				}
				throw new Exception( 'No se encontró usuario con el correo proporcionado' );
			}
			throw new Exception( 'Error con la conexión a la fuente de información' );
		}
		public function validateAccess ( string $login, string $password, int $platform ): array {
			$query = "SELECT id FROM users WHERE (nickname = '$login' AND password = '$password') OR (email = '$login' AND password = '$password') AND active = 1";
			$res = $this->db->query ( $query );
			if ( $res->getNumRows () === 0 ) {
				return [ FALSE, $res->getNumRows () ];
			}
			$user = $res->getResultArray ()[0]['id'];
			$query = "SELECT t4.name, t4.session, t4.route, t3.writable
FROM users t1
    INNER JOIN platform_access t2 ON t1.id  = t2.id_user AND t2.id_platform = $platform
    INNER JOIN permissions t3 ON t3.user_id = t1.id
    INNER JOIN views t4 ON t4.id = t3.view_id
WHERE t2.id_platform = $platform AND t1.id  = $user";
			$res = $this->db->query ( $query );
			if ( $res->getNumRows () === 0 ) {
				return [ FALSE, $res->getNumRows () ];
			}
			$data = ['id' => $user, 'permissions' => $res->getResultArray ()];
			return [ TRUE, $data];
		}
	}