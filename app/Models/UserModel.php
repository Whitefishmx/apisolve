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
			$query = "SELECT t1.id, t1.email, t2.name, t2.last_name, t2.sure_name, t2.rfc, t2.curp, t3.net_salary, t3.plan, t1.first_login,
       t2.id AS 'personId', t3.id AS 'employeId', t3.company_id AS 'companyId'
FROM users t1
    INNER JOIN employee_user eu ON t1.id  = eu.user_id
    INNER JOIN employee t3 ON t3.id = eu.employee_id
    INNER JOIN person_user pu ON t1.id = pu.user_id
    INNER JOIN person t2 ON pu.person_id = t2.id
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
			$query = "SELECT t1.id, t1.email, t2.name, t2.last_name, t2.sure_name, t2.rfc, t2.curp, t3.net_salary, t3.plan, t1.first_login, t3.company_id, c.short_name,
       t1.id AS 'userId', t2.id AS 'personId', t3.id AS 'employeId', t3.company_id AS 'companyId'
FROM users t1
INNER JOIN employee_user eu ON t1.id  = eu.user_id
INNER JOIN employee t3 ON t3.id = eu.employee_id
INNER JOIN person_user pu ON t1.id = pu.user_id
INNER JOIN person t2 ON pu.person_id = t2.id
INNER JOIN companies c ON t3.company_id = c.id
WHERE (t1.nickname = '$login' AND t1.password = '$password')
   OR (t1.email = '$login' AND t1.password = '$password') ";
			if ( $platform === 6 ) {
				$query .= "OR (t2.curp = '$login' AND t1.password = '$password') ";
			}
			$query .= "AND t1.active = 1 AND t3.status = 1 AND t2.active = 1";
			//						var_dump ($query);die();
			$res = $this->db->query ( $query );
			//			var_dump($res->getNumRows ());die();
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
			//						var_dump ($query);die();
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
				saveLog ( $user, 22, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 22, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 22, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ), json_encode (
				$res->getResultArray ()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
		public function updateProfile ( string $nickname, string $email, string $password, $phone, int $user ): array {
			$query = "UPDATE users SET nickname = '$nickname', email = '$email', password = '$password' WHERE id = $user";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					if ( $phone !== NULL ) {
						$query = "UPDATE person SET phone = '$phone' WHERE id = $user";
						if ( $this->db->query ( $query ) ) {
							$affected = $this->db->affectedRows ();
							if ( $affected > 0 ) {
								saveLog ( $user, 32, 200, json_encode ( [ 'newPassword' => $password, ] ), json_encode
								( [ 'affected' => $affected ] ) );
								return [ TRUE, 'Se actualizó correctamente el perfil' ];
							}
						}
					}
					saveLog ( $user, 32, 200, json_encode ( [ 'newPassword' => $password, ] ), json_encode
					( [ 'affected' => $affected ] ) );
					return [ TRUE, 'Se actualizó correctamente el perfil' ];
				}
				saveLog ( $user, 32, 404, json_encode ( [ 'newPassword' => $password, ] ), json_encode ( [ FALSE, 'affected' => $affected ] ) );
				return [ FALSE, 'No se encontró registro a actualizar' ];
			}
			saveLog ( $user, 32, 500, json_encode ( [ 'newPassword' => $password, ] ), json_encode ( [ FALSE, 'affected' => $this->db->error () ] ) );
			return [ FALSE, 'No se pudo actualizar el perfil' ];
		}
		public function getExpressProfile ( $user ): array {
			$query = "SELECT u.id as userId, p.id as personId, e.id as employeeId, p.name, p.last_name, p.sure_name, p.curp, p.phone,
       u.nickname, u.email, c.short_name, CONCAT('**** **** ****** ', SUBSTRING(ba.clabe, -4)) as 'clabe'
				FROM users u
    INNER JOIN employee_user eu ON u.id  = eu.user_id
    INNER JOIN employee e ON e.id = eu.employee_id
    INNER JOIN person_user pu ON u.id = pu.user_id
    INNER JOIN person p ON pu.person_id = p.id
				    INNER JOIN companies c ON c.id = e.company_id
				    INNER JOIN bank_accounts ba ON (ba.user_id  = u.id OR ba.person_id = p.id) AND ba.company_id= c.id
				WHERE u.id = $user";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 48, 404, json_encode ( [ 'user' => $user ] ), json_encode ( [ FALSE, 'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 48, 404, json_encode ( [ 'user' => $user ] ), json_encode ( [ FALSE, 'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 48, 200, json_encode ( [ 'user' => $user ] ), json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
		public function getUserByMail ( $email ): array {
			$query = "SELECT u.id as userId, p.id as personId, e.id as employeeId, CapitalizarTexto(p.name) AS name,
       CapitalizarTexto(p.last_name) AS lastName, CapitalizarTexto(p.sure_name) AS sureName,u.email
FROM users u
    INNER JOIN employee_user eu ON u.id  = eu.user_id
    INNER JOIN employee e ON e.id = eu.employee_id
    INNER JOIN person_user pu ON u.id = pu.user_id
    INNER JOIN person p ON pu.person_id = p.id
WHERE u.email = '$email' AND e.status = 1 AND p.active = 1 AND u.active = 1 ";
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return [ TRUE, $res->getResultArray () ];
				}
				return [ FALSE, 'No se encontró usuario con el correo proporcionado' ];
			}
			return [ FALSE, 'Error con la conexión a la fuente de información' ];
		}
		public function setRecoveryCode ( $code, $user ): bool {
			$query = "UPDATE users SET recovery_code = '$code', recovery_date = NOW(), active = 0 WHERE id = $user";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					return TRUE;
				}
				return FALSE;
			}
			return FALSE;
		}
		public function validateRecoveryCode ( $user, $code, $resetCode ): bool {
			$query = "UPDATE users SET recovery_code = NULL, recovery_date = NULL, reset_code = '$resetCode', reset_date = NOW()
             WHERE id = $user AND recovery_code = '$code'";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					return TRUE;
				}
				return FALSE;
			}
			return FALSE;
		}
		public function resetPassword ( mixed $user, string $code, string $password ): bool {
			$query = "UPDATE users SET password = '$password', active = 1, reset_code = NULL, reset_date = NOW() WHERE id = $user AND reset_code = '$code'";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					return TRUE;
				}
				return FALSE;
			}
			return FALSE;
		}
		public function checkExistByCurp ( $curp, $company ): array {
			$query = "SELECT p.id AS 'personId', e.id AS 'employeeId', u.id AS 'userId'
FROM users u
    INNER JOIN employee_user eu ON u.id  = eu.user_id
    INNER JOIN employee e ON e.id = eu.employee_id
    INNER JOIN person_user pu ON u.id = pu.user_id
    INNER JOIN person p ON pu.person_id = p.id
WHERE p.curp = '$curp' AND e.company_id = '$company'";
			//			var_dump ($query);die();
			if ( $res = $this->db->query ( $query ) ) {
				if ( $res->getNumRows () > 0 ) {
					return [ TRUE, $res->getResultArray ()[ 0 ] ];
				}
				return [ FALSE, [ 'error' => 'No existe' ] ];
			}
			return [ FALSE, [ 'error' => 'No con conexión' ] ];
		}
		public function existsByCurp ( $curp ): array {
			$query = "SELECT p.id as 'personId', e.id as 'employeeId', u.id as 'userId'
FROM person p
INNER JOIN employee e ON e.person_id = p.id
LEFT JOIN person_user pu ON pu.person_id = p.id
INNER JOIN users u ON u.id = pu.user_id AND p.primary_user_id = u.id
WHERE p.curp = '$curp'";
			if ( $res = $this->db->query ( $query ) ) {
				return [ TRUE, $res->getResultArray ()[ 0 ] ];
			}
			return [ FALSE, 'No se encontraron resultados' ];
		}
		public function getDataForAfiliation ( $user ): array {
			$query = "SELECT u.id AS 'userID', p.id AS 'personId', e.id AS 'employeeId', p.rfc, p.phone, apr.planBenefit, cpb.plan,
       CapitalizarTexto(p.name) AS 'name', CapitalizarTexto(p.last_name) AS 'last_name', CapitalizarTexto(p.sure_name) AS 'sure_name'
FROM users u
    INNER JOIN employee_user eu ON u.id  = eu.user_id
    INNER JOIN employee e ON eu.employee_id = e.id
    INNER JOIN employee_benefits eb ON eb.employee_id = e.id
    INNER JOIN person p ON p.id = e.person_id
    INNER JOIN advancePayroll_rules apr ON apr.company_id = e.company_id
    INNER JOIN cat_planBenefits cpb ON cpb.id = apr.planBenefit
WHERE u.id = $user ";
			if ( $res = $this->db->query ( $query ) ) {
				return [ TRUE, $res->getRowArray () ];
			}
			return [ FALSE, 'No se encontraron resultados' ];
		}
	}