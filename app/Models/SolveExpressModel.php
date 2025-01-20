<?php
	
	namespace App\Models;
	
	use DateTime;
	use CodeIgniter\Database\Query;
	use CodeIgniter\Database\BaseResult;
	use DateMalformedStringException as DateMalformedStringExceptionAlias;
	
	class SolveExpressModel extends BaseModel {
		//		private float $commissions = 70;
		private float $commissions = 1;
		public function updateFlagCurp ( $employee, $fingerprint ): array {
			$query = "UPDATE employee set curp_validated = 1, device = '$fingerprint' WHERE id = '$employee'";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					//					saveLog ( $user, 24, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
					//					( [ 'affected' => $affected ] ) );
					return [ TRUE, 'Se actualizó el estatus de validación' ];
				}
				//				saveLog ( $user, 24, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
				//				( [ FALSE, 'affected' => $affected ] ) );
				return [ FALSE, 'No se encontró registro a actualizar' ];
			}
			//			saveLog ( $user, 24, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
			//			( [ FALSE, 'affected' => $this->db->error () ] ) );
			return [ FALSE, 'No se pudo actualizar el estado de la validación' ];
		}
		public function verifyCurp ( $curp ): array {
			$query = "SELECT p.curp, u.id, u.password, e.curp_validated, e.device, e.metamap, e.id as 'employee'
FROM users u
INNER JOIN employee_user eu ON u.id  = eu.user_id
INNER JOIN employee e ON e.id = eu.employee_id
INNER JOIN person_user pu ON u.id = pu.user_id
INNER JOIN person p ON pu.person_id = p.id
WHERE p.active = 1 and e.status = 1 AND u.active =1 AND u.email IS NULL AND u.password IS NULL AND p.curp = '$curp'";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( 1, 38, 404, json_encode ( [ 'curp' => $curp ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows === 0 ) {
				saveLog ( 1, 38, 404, json_encode ( [ 'curp' => $curp ] ), json_encode ( [
					FALSE,
					'La curp ingresada no esta registrada, contacta a recursos humanos' ] ) );
				return [ FALSE, 'La curp ingresada no esta registrada, contacta a recursos humanos' ];
			}
			saveLog ( 1, 38, 200, json_encode ( [ 'curp' => $curp ] ), json_encode ( $res->getResultArray (), TRUE ) );
			if ( $res->getNumRows () > 1 ) {
				$message = "Registro erroneo con la CURP ingresada, contacte a soporte tecnico";
				saveLog ( 1, 38, 404, json_encode ( [ 'curp' => $curp ] ), json_encode ( [ FALSE, $message ] ) );
				return [ FALSE, $message ];
			}
			$res = $res->getResultArray ();
			if ( $res[ 0 ][ 'password' ] != NULL ) {
				$message = "La CURP ingresada ya fue verificada y tiene un usuario activo";
				saveLog ( 1, 38, 404, json_encode ( [ 'curp' => $curp ] ), json_encode ( [ FALSE, $message ] ) );
				return [ FALSE, $message ];
			}
			return [ TRUE, $res[ 0 ] ];
		}
		/** @noinspection DuplicatedCode */
		public function getReport ( array $args, int $user ): array {
			$builder = $this->db->table ( 'users u' )
			                    ->select ( "p.name, p.last_name, p.sure_name, p.rfc, p.curp, e.external_id, e.plan, e.net_salary,
              t1.requested_amount, t1.remaining_amount, t1.period, t1.folio, t2.noReference, t2.cep,
              t3.clabe, t3.card, t4.bnk_alias, FORMAT_TIMESTAMP(t1.created_at) AS 'Fecha_solicitud', FORMAT_TIMESTAMP(t2.created_at) AS 'Ultima_modificacion'" )
			                    ->join ( 'employee_user eu', 'u.id  = eu.user_id', 'inner' )
			                    ->join ( 'employee e', 'eu.employee_id = e.id', 'inner' )
			                    ->join ( 'person_user pu', 'u.id = pu.user_id', 'inner' )
			                    ->join ( 'person p', 'p.id = pu.person_id', 'inner' )
			                    ->join ( 'companies c', 'e.company_id = c.id', 'inner' )
			                    ->join ( 'advance_payroll t1', 't1.employee_id = e.id ', 'inner' )
			                    ->join ( 'transactions t2', 't2.payroll_id = t1.id', 'left' )
			                    ->join ( 'bank_accounts t3', 't3.id = t2.account_destination', 'left' )
			                    ->join ( 'cat_bancos t4', 't4.id = t3.bank_id', 'inner' );
			if ( !empty( $args[ 'user' ] ) ) {
				$builder->where ( 'u.id', $args[ 'user' ] );
			}
			if ( !empty( $args[ 'employee' ] ) ) {
				$builder->where ( 'e.external_id', $args[ 'employee' ] );
			}
			if ( !empty( $args[ 'company' ] ) ) {
				$builder->where ( 'c.id', $args[ 'company' ] );
			}
			if ( !empty( $args[ 'initDate' ] ) ) {
				try {
					$initDate = new DateTime( $args[ 'initDate' ] );
					$initDate->setTime ( 0, 0 );
					$initDate = $initDate->format ( 'Y-m-d H:i:s' );
					$builder->where ( 't1.created_at >=', $initDate );
				} catch ( DateMalformedStringExceptionAlias ) {
					$builder->where ( 't1.created_at >=', $args[ 'initDate' ] );
				}
			}
			if ( !empty( $args[ 'endDate' ] ) ) {
				try {
					$fechaFin = new DateTime( $args[ 'endDate' ] );
					$fechaFin->setTime ( 0, 0 );
					$fechaFin = $fechaFin->modify ( '+1 day' )->format ( 'Y-m-d H:i:s' );
					$builder->where ( 't2.created_at <=', $fechaFin );
				} catch ( DateMalformedStringExceptionAlias ) {
					$builder->where ( 't2.created_at <=', $args[ 'endDate' ] );
				}
			}
			if ( !empty( $args[ 'plan' ] ) ) {
				$builder->where ( 'e.plan', $args[ 'plan' ] );
			}
			if ( !empty( $args[ 'rfc' ] ) ) {
				$builder->like ( 'p.rfc', $args[ 'rfc' ] );
			}
			if ( !empty( $args[ 'curp' ] ) ) {
				$builder->like ( 'p.curp', $args[ 'curp' ] );
			}
			if ( !empty( $args[ 'name' ] ) ) {
				$builder->like ( 'p.name', $args[ 'name' ] );
			}
			$builder->orderBy ( 't1.created_at', 'DESC' );
			$sqlQuery = $builder->getCompiledSelect ();
			if ( !$res = $this->db->query ( $sqlQuery ) ) {
				saveLog ( $user, 14, 404, json_encode ( [ 'args' => $args ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 0 ) {
				if ( $res->getNumRows () === 1 ) {
					saveLog ( $user, 12, 200, json_encode ( [ 'args' => $args ] ), json_encode ( $res->getResult ()[ 0 ] ) );
					return [ TRUE, $res->getResult ()[ 0 ] ];
				}
				$res = $res->getResultArray ();
				saveLog ( $user, 12, 200, json_encode ( [ 'args' => $args ] ), json_encode ( $res ) );
				return [ TRUE, $res ];
			} else {
				saveLog ( $user, 12, 404, json_encode ( [ 'args' => $args ] ), json_encode
				( [ 'res' => 'No se encontraron resultados' ] ) );
				return [ FALSE, 'No se encontraron resultados' ];
			}
		}
		/** @noinspection DuplicatedCode */
		public function getReportCompany ( array $args, int $user ): array {
			$builder = $this->db->table ( 'users u' )
			                    ->select ( "e.external_id, p.curp, e.net_salary, t1.requested_amount,
			                    CapitalizarTexto(p.name) AS 'name', CapitalizarTexto(p.last_name) AS 'last_name', CapitalizarTexto(p.sure_name) AS 'sure_name',
			                    CASE WHEN e.plan = 'q' THEN (e.net_salary/2)-(t1.requested_amount)
			                    WHEN e.plan = 'm' THEN (e.net_salary)-(t1.requested_amount)
			                    WHEN plan = 's' THEN (e.net_salary/2)-(t1.requested_amount)
			                    END AS 'remaining_amount', t1.period, FORMAT_TIMESTAMP(t1.created_at) AS 'request_date' " )
			                    ->join ( 'employee_user eu', 'u.id  = eu.user_id', 'inner' )
			                    ->join ( 'employee e', 'eu.employee_id = e.id', 'inner' )
			                    ->join ( 'person_user pu', 'u.id = pu.user_id', 'inner' )
			                    ->join ( 'person p', 'p.id = pu.person_id', 'inner' )
			                    ->join ( 'companies c', 'e.company_id = c.id', 'inner' )
			                    ->join ( 'advance_payroll t1', 't1.employee_id = e.id ', 'inner' )
			                    ->join ( 'transactions t2', 't2.payroll_id = t1.id', 'left' )
			                    ->join ( 'bank_accounts t3', 't3.id = t2.account_destination', 'left' )
			                    ->join ( 'cat_bancos t4', 't4.id = t3.bank_id', 'inner' )
			                    ->where ( 'u.type', '1' );
			if ( !empty( $args[ 'employee' ] ) ) {
				$builder->where ( 'e.external_id', $args[ 'employee' ] );
			}
			if ( !empty( $args[ 'company' ] ) ) {
				$builder->where ( 'c.id', $args[ 'company' ] );
			}
			if ( !empty( $args[ 'initDate' ] ) ) {
				try {
					$initDate = new DateTime( $args[ 'initDate' ] );
					$initDate->setTime ( 0, 0 );
					$initDate = $initDate->format ( 'Y-m-d H:i:s' );
					$builder->where ( 't1.created_at >=', $initDate );
				} catch ( DateMalformedStringExceptionAlias ) {
					$builder->where ( 't1.created_at >=', $args[ 'initDate' ] );
				}
			}
			if ( !empty( $args[ 'endDate' ] ) ) {
				try {
					$fechaFin = new DateTime( $args[ 'endDate' ] );
					$fechaFin->setTime ( 0, 0 );
					$fechaFin = $fechaFin->modify ( '+1 day' )->format ( 'Y-m-d H:i:s' );
					$builder->where ( 't2.created_at <=', $fechaFin );
				} catch ( DateMalformedStringExceptionAlias ) {
					$builder->where ( 't2.created_at <=', $args[ 'endDate' ] );
				}
			}
			if ( !empty( $args[ 'plan' ] ) ) {
				$builder->where ( 'e.plan', $args[ 'plan' ] );
			}
			if ( !empty( $args[ 'period' ] ) ) {
				$builder->like ( 't1.period', $args[ 'period' ] );
			}
			if ( !empty( $args[ 'rfc' ] ) ) {
				$builder->like ( 'p.rfc', $args[ 'rfc' ] );
			}
			if ( !empty( $args[ 'curp' ] ) ) {
				$builder->like ( 'p.curp', $args[ 'curp' ] );
			}
			if ( !empty( $args[ 'name' ] ) ) {
				$builder->like ( 'p.name', $args[ 'name' ] );
			}
			$sqlQuery = $builder->getCompiledSelect ();
			die($sqlQuery);
			if ( !$res = $this->db->query ( $sqlQuery ) ) {
				saveLog ( $user, 14, 404, json_encode ( [ 'args' => $args ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 0 ) {
				if ( $res->getNumRows () === 1 ) {
					saveLog ( $user, 12, 200, json_encode ( [ 'args' => $args ] ), json_encode ( $res->getResult ()[ 0 ] ) );
					return [ TRUE, $res->getResult () ];
				}
				$res = $res->getResultArray ();
				saveLog ( $user, 12, 200, json_encode ( [ 'args' => $args ] ), json_encode ( $res ) );
				return [ TRUE, $res ];
			} else {
				saveLog ( $user, 12, 404, json_encode ( [ 'args' => $args ] ), json_encode
				( [ 'res' => 'No se encontraron resultados' ] ) );
				return [ FALSE, 'No se encontraron resultados' ];
			}
		}
		/** @noinspection DuplicatedCode */
		public function getReportCompanyV2 ( array $args, array $columns, int $user ): array {
			$builder = $this->db->table ( 'users u' );
			if ( in_array ( 'noEmpleado', $columns ) ) {
				$builder->select ( "e.external_id AS '#Empleado'" );
			}
			if ( in_array ( 'name', $columns ) ) {
				$builder->select ( "CapitalizarTexto(p.name) AS 'Nombre'" );
			}
			if ( in_array ( 'lastName', $columns ) ) {
				$builder->select ( "CapitalizarTexto(p.last_name) AS 'Apellido Paterno'" );
			}
			if ( in_array ( 'sureName', $columns ) ) {
				$builder->select ( "CapitalizarTexto(p.sure_name) AS 'Apellido Materno'" );
			}
			if ( in_array ( 'rfc', $columns ) ) {
				$builder->select ( "p.rfc AS 'RFC'" );
			}
			if ( in_array ( 'curp', $columns ) ) {
				$builder->select ( "p.curp AS 'CURP'" );
			}
			if ( in_array ( 'plan', $columns ) ) {
				$builder->select ( "CASE WHEN e.plan = 'q' THEN 'Quincenal' WHEN e.plan = 'm' THEN 'Mensual' WHEN plan = 's' THEN 'Semanal' ELSE 'Otro' END AS 'Esquema'" );
			}
			if ( in_array ( 'netSalary', $columns ) ) {
				$builder->select ( "e.net_salary AS 'Salario Neto'" );
			}
			if ( in_array ( 'period', $columns ) ) {
				$builder->select ( "t1.period AS 'Periodo'" );
			}
			$builder->select ( "t1.requested_amount AS 'Monto solicitado',
			                    CASE WHEN e.plan = 'q' THEN (e.net_salary/2)-(t1.requested_amount)
			                    WHEN e.plan = 'm' THEN (e.net_salary)-(t1.requested_amount)
			                    WHEN plan = 's' THEN (e.net_salary/2)-(t1.requested_amount)
			                    END AS 'Monto restante', FORMAT_TIMESTAMP(t1.created_at) AS 'Fecha de solicitud',
			                    apr.concept AS 'Concepto'" )
			        ->join ( 'employee_user eu', 'u.id  = eu.user_id', 'inner' )
			        ->join ( 'employee e', 'eu.employee_id = e.id', 'inner' )
			        ->join ( 'person_user pu', 'u.id = pu.user_id', 'inner' )
			        ->join ( 'person p', 'p.id = pu.person_id', 'inner' )
			        ->join ( 'companies c', 'e.company_id = c.id', 'inner' )
			        ->join ( 'advance_payroll t1', 't1.employee_id = e.id ', 'inner' )
			        ->join ( 'transactions t2', 't2.payroll_id = t1.id', 'left' )
			        ->join ( 'bank_accounts t3', 't3.id = t2.account_destination', 'left' )
			        ->join ( 'cat_bancos t4', 't4.id = t3.bank_id', 'inner' )
			        ->join ( 'advancePayroll_rules apr', 'apr.company_id = c.id', 'INNER' )
				->where ( 'u.type', '1' );
			if ( !empty( $args[ 'employee' ] ) ) {
				$builder->where ( 'e.external_id', $args[ 'employee' ] );
			}
			if ( !empty( $args[ 'company' ] ) ) {
				$builder->where ( 'c.id', $args[ 'company' ] );
			}
			if ( !empty( $args[ 'initDate' ] ) ) {
				try {
					$initDate = new DateTime( $args[ 'initDate' ] );
					$initDate->setTime ( 0, 0 );
					$initDate = $initDate->format ( 'Y-m-d H:i:s' );
					$builder->where ( 't1.created_at >=', $initDate );
				} catch ( DateMalformedStringExceptionAlias ) {
					$builder->where ( 't1.created_at >=', $args[ 'initDate' ] );
				}
			}
			if ( !empty( $args[ 'endDate' ] ) ) {
				try {
					$fechaFin = new DateTime( $args[ 'endDate' ] );
					$fechaFin->setTime ( 0, 0 );
					$fechaFin = $fechaFin->modify ( '+1 day' )->format ( 'Y-m-d H:i:s' );
					$builder->where ( 't2.created_at <=', $fechaFin );
				} catch ( DateMalformedStringExceptionAlias ) {
					$builder->where ( 't2.created_at <=', $args[ 'endDate' ] );
				}
			}
			if ( !empty( $args[ 'plan' ] ) ) {
				$builder->where ( 'e.plan', $args[ 'plan' ] );
			}
			if ( !empty( $args[ 'period' ] ) ) {
				$builder->like ( 't1.period', $args[ 'period' ] );
			}
			if ( !empty( $args[ 'rfc' ] ) ) {
				$builder->like ( 'p.rfc', $args[ 'rfc' ] );
			}
			if ( !empty( $args[ 'name' ] ) ) {
				$builder->like ( 'p.name', $args[ 'name' ] );
			}
			$sqlQuery = $builder->getCompiledSelect ();
//			var_dump ($sqlQuery); die();
			if ( !$res = $this->db->query ( $sqlQuery ) ) {
				saveLog ( $user, 14, 404, json_encode ( [ 'args' => $args ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 0 ) {
				if ( $res->getNumRows () === 1 ) {
					saveLog ( $user, 12, 200, json_encode ( [ 'args' => $args ] ), json_encode ( $res->getResult ()[ 0 ] ) );
					return [ TRUE, json_decode ( json_encode ( $res->getResult () ), TRUE ) ];
				}
				$res = $res->getResultArray ();
				saveLog ( $user, 12, 200, json_encode ( [ 'args' => $args ] ), json_encode ( $res ) );
				return [ TRUE, json_decode ( json_encode ( $res ), TRUE ) ];
			} else {
				saveLog ( $user, 12, 404, json_encode ( [ 'args' => $args ] ), json_encode
				( [ 'res' => 'No se encontraron resultados' ] ) );
				return [ FALSE, 'No se encontraron resultados' ];
			}
		}
		public function getPeriods ( $company, $user ): array {
			$query = "SELECT ap.period FROM advance_payroll ap INNER JOIN employee e ON e.id = ap.employee_id WHERE e.company_id = $company
                                                                                        GROUP BY ap.period ORDER BY MAX(ap.created_at) DESC";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 35, 404, json_encode ( [ 'company' => $company ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows === 0 ) {
				saveLog ( $user, 35, 404, json_encode ( [ 'company' => $company ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 35, 200, json_encode ( [ 'company' => $company ] ), json_encode (
				$res->getResultArray
				(), TRUE ) );
			if ( $res->getNumRows () > 1 ) {
				$p = [];
				foreach ( $res->getResultArray () as $row ) {
					$p[] = $row[ 'period' ];
				}
				return [ TRUE, $p ];
			}
			return [ TRUE, $res->getResultArray () ];
		}
		public function getDashboard ( int $user ): array {
			$query = "SELECT u.id as userId, p.id as personId, e.id as employeeId,
       p.name, p.last_name, p.sure_name, c.short_name, RoundDown(e.net_salary) AS 'net_salary', e.plan, RoundDown(t1.amount_available) AS 'amount_available', t1.worked_days, t1.available,
       apr.min_amount, apr.max_amount, apr.commission, CONCAT('**** **** ****** ', SUBSTRING(ba.clabe, -4)) as clabe, t1.req_day, t1.req_biweekly, t1.req_month, apr.limit_day, apr.limit_biweekly, apr.limit_month
    FROM users u
        INNER JOIN employee_user eu ON u.id  = eu.user_id
        INNER JOIN employee e ON e.id = eu.employee_id
        INNER JOIN person_user pu ON u.id = pu.user_id
        INNER JOIN person p ON pu.person_id = p.id
        INNER JOIN advancePayroll_control t1 ON t1.employee_id = e.id
        INNER JOIN companies c ON c.id = e.company_id
    	INNER JOIN advancePayroll_rules apr ON apr.company_id = c.id
        INNER JOIN bank_accounts ba ON ba.user_id  = u.id AND ba.company_id= c.id
        WHERE u.id = ?";
			//									var_dump ($query);die();
			if ( !$res = $this->db->query ( $query, [ $user ] ) ) {
				saveLog ( $user, 14, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 14, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 14, 200, json_encode ( [] ), json_encode (
				$res->getResultArray
				()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
		public function getCompanyRules ( int $user ): array {
			$query = "SELECT r.*
FROM users t1
INNER JOIN employee_user eu ON t1.id  = eu.user_id
INNER JOIN employee t3 ON t3.id = eu.employee_id
INNER JOIN person_user pu ON t1.id = pu.user_id
INNER JOIN person t2 ON pu.person_id = t2.id
INNER JOIN companies c ON t3.company_id = c.id
    INNER JOIN advancePayroll_rules r ON r.company_id = c.id
    WHERE t1.id = $user";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 17, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 17, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 17, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ), json_encode (
				$res->getResultArray ()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
		public function getControlByUSer ( int $user ): array {
			$query = "SELECT c.*
FROM users u
INNER JOIN employee_user eu ON u.id  = eu.user_id
INNER JOIN employee e ON e.id = eu.employee_id
INNER JOIN person_user pu ON u.id = pu.user_id
INNER JOIN person p ON pu.person_id = p.id
       INNER JOIN advancePayroll_control c ON c.employee_id = e.id
    WHERE u.id = $user";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 18, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 18, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 18, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $user ) ] ), json_encode (
				$res->getResultArray ()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
		/**
		 * @throws DateMalformedStringExceptionAlias
		 */
		public function generateOrder ( int $user, float $amount, $preRemaining, $plan, $commission ): array {
			$folio = $this->generateFolio ( 19, 'advance_payroll', $user );
			$nexID = $this->getNexId ( 'advance_payroll' );
			$refNumber = $this->NewReferenceNumber ( $nexID );
			$employee = $this->getEmployeeByIdUser ( $user )[ 0 ][ 'id' ];
			$remaining = $preRemaining - $amount;
			$period = $this->getPeriod ( $plan );
			$dataIn = [ $employee, $folio, $refNumber, $amount, $remaining, $period ];
			$query = "INSERT INTO advance_payroll (employee_id, folio, reference_number, requested_amount, remaining_amount, period)
VALUES ($employee, '$folio', '$refNumber', $amount, $remaining, '$period')";
			$this->db->query ( 'SET NAMES utf8mb4' );
			if ( $this->db->query ( $query ) ) {
				$id = $this->db->insertId ();
				saveLog ( $user, 19, 200, json_encode ( $dataIn ), json_encode ( [ 'id' => $id ], TRUE ) );
				$dataOut = [ 'folio' => $folio, 'refNumber' => $refNumber, 'amount' => $amount - $commission, 'payrollId' => $id ];
				return [ TRUE, $dataOut ];
			} else {
				saveLog ( $user, 19, 400, json_encode ( $dataIn ), json_encode ( [
					FALSE,
					'No se pudo generar el pedido' ] ) );
				return [ FALSE, 'No se pudo generar el pedido' ];
			}
		}
		/**
		 * @throws DateMalformedStringExceptionAlias
		 */
		public function getPeriod ( $plan ): string {
			helper ( 'tools_helper' );
			$date = new DateTime( date ( 'Y-m-d', strtotime ( 'now' ) ) );
			$mes = month2Mes ( $date->format ( 'n' ) );
			$dia = $date->format ( 'd' );
			$anio = $date->format ( 'Y' );
			$semanaDelMes = ceil ( $dia / 7 );
			switch ( $plan ) {
				case 's':
					return "{$semanaDelMes}ª semana de $mes $anio";
				case 'q':
					if ( $dia <= 15 ) {
						return "1ª quincena de $mes $anio";
					} else {
						return "2ª quincena de $mes $anio";
					}
				case 'm':
					return "$mes $anio";
				default:
					return "Plan de pago no válido.";
			}
		}
		/*public function updateOperationStatus ( $folio, $noRef, $status, $user ): array {
			$query = "UPDATE advance_payroll SET status = '$status' WHERE folio like '%$folio%' AND reference_number = '$noRef'";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					saveLog ( $user, 24, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
					( [ 'affected' => $affected ] ) );
					return [ TRUE, 'Se actualizó el estado de las transacciones' ];
				}
				saveLog ( $user, 24, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
				( [ FALSE, 'affected' => $affected ] ) );
				return [ FALSE, 'No se encontró registro a actualizar' ];
			}
			saveLog ( $user, 24, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
			( [ FALSE, 'affected' => $this->db->error () ] ) );
			return [ FALSE, 'No se pudo actualizar el estado de las transacciones' ];
		}*/
		public function updateAvailableAmount ( int $employeeId, float $requestAmount, int $user ): array {
			
			$query = "UPDATE advancePayroll_control
SET amount_available = amount_available-$requestAmount, req_day = req_day+1, req_week = req_week+1, req_biweekly = req_biweekly+1, req_month = req_month+1
WHERE employee_id = $employeeId";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					saveLog ( $user, 25, 200, json_encode ( [ 'requestAmount' => $requestAmount ] ), json_encode
					( [ 'affected' => $affected ] ) );
					return [ TRUE, 'Se actualizó el estado de las transacciones' ];
				}
				saveLog ( $user, 25, 200, json_encode ( [ 'requestAmount' => $requestAmount ] ), json_encode
				( [ FALSE, 'affected' => $affected ] ) );
				return [ FALSE, 'No se encontró registro a actualizar' ];
			}
			saveLog ( $user, 25, 200, json_encode ( [ 'requestAmount' => $requestAmount ] ), json_encode
			( [ FALSE, 'affected' => $this->db->error () ] ) );
			return [ FALSE, 'No se pudo actualizar el estado de las transacciones' ];
		}
		public function updateMetaValidation ( mixed $curp, int $score, int $user ): array {
			$approved = "UPDATE employee set metamap = 1 WHERE employee.person_id = (SELECT id FROM person WHERE curp = '$curp')";
			$reject = "UPDATE employee set metamap = 0, curp_validated = 0, device = NULL WHERE employee.person_id = (SELECT id FROM person WHERE curp = '$curp')";
			$query = $score > 0 ? $approved : $reject;
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					saveLog ( $user, 41, 200, json_encode ( [ 'score' => $score ] ), json_encode
					( [ 'affected' => $affected ] ) );
					return [ TRUE, 'Se actualizó el estado de las transacciones' ];
				}
				saveLog ( $user, 41, 200, json_encode ( [ 'score' => $score ] ), json_encode
				( [ FALSE, 'affected' => $affected ] ) );
				return [ FALSE, 'No se encontró registro a actualizar' ];
			}
			saveLog ( $user, 25, 200, json_encode ( [ [ 'score' => $score ] ] ), json_encode
			( [ FALSE, 'affected' => $this->db->error () ] ) );
			return [ FALSE, 'No se pudo actualizar el estado de las transacciones' ];
		}
		public function getPeriodsCompany ( $company_id, $current_date, $user ): array {
			$query = "SELECT * FROM payroll_periods
              WHERE company_id = $company_id
              AND start_date <= '$current_date'
              AND end_date >= '$current_date'
              ORDER BY start_date
              LIMIT 1";
			$res = $this->db->query ( $query )->getResultArray ();
			if ( !$res ) {
				/*saveLog ( $user, 46, 404, json_encode ( [ 'args' => [ $company_id, $current_date, $user ] ] ), json_encode ( [
					FALSE,
					'No se encontró información',
				] ) );*/
				return [ FALSE, 'No se encontró información' ];
			}
			//saveLog ( $user, 46, 200, json_encode ( [ 'args' => [ $company_id, $current_date, $user ] ] ), json_encode ( $res ) );
			return $res[ 0 ];
		}
		public function getSumRequest ( $employee, $period_name ) {
			return $this->db->query ( "
                    SELECT IFNULL(SUM(requested_amount), 0) AS total_requested
                    FROM advance_payroll
                    WHERE employee_id = ?
                      AND period = ?
                ", [ $employee[ 'id' ], $period_name ] )->getRow ()->total_requested;
		}
		//		public function updateNomina ( $args, $company, $user ) {
		//			//				$query="INSERT INTO person (name, last_name, sure_name, active, rfc, curp, iv)
		//			//values ('{$args['Nombre']}', '{$args['Apellido paterno']}', '{$args['Apellido materno']}','{$args['Estatus']}','{$args['RFC']}',
		//			//        '{$args['CURP']}','{$args['iv']}')
		//			//ON DUPLICATE KEY UPDATE name = '{$args['Nombre']}', last_name = '{$args['Nombre']}', sure_name = '{$args['Nombre']}', full_name = '{$args['Nombre']}',
		//			//                        active= '{$args['Nombre']}', rfc = '{$args['Nombre']}', curp = '{$args['CURP']}' ";
		//			//			if ( $this->db->query ( $query ) ) {
		//			//				$affected = $this->db->affectedRows ();
		//			//				if ( $affected > 0 ) {
		//			//					saveLog ( $user, 41, 200, json_encode ( [ 'score' => $score ] ), json_encode
		//			//					( [ 'affected' => $affected ] ) );
		//			//					return [ TRUE, 'Se actualizó el estado de las transacciones' ];
		//			//				}
		//			//				saveLog ( $user, 41, 200, json_encode ( [ 'score' => $score ] ), json_encode
		//			//				( [ FALSE, 'affected' => $affected ] ) );
		//			//				return [ FALSE, 'No se encontró registro a actualizar' ];
		//			//			}
		//			//			saveLog ( $user, 25, 200, json_encode ( [ [ 'score' => $score ] ] ), json_encode
		//			//			( [ FALSE, 'affected' => $this->db->error () ] ) );
		//			//			return [ FALSE, 'No se pudo actualizar el estado de las transacciones' ];
		//		}
		public function resetCounters ( $company_id, $current_date ): void {
			$this->db->query ( "UPDATE advancePayroll_control
            SET req_day = 0,
                req_week = IF(WEEK(?) > WEEK(updated_at), 0, req_week),
                req_biweekly = IF(WEEK(?) > WEEK(updated_at) + 2, 0, req_biweekly),
                req_month = IF(MONTH(?) != MONTH(updated_at), 0, req_month)
            WHERE employee_id IN (
                SELECT id FROM employee WHERE company_id = ?
            )
        ", [ $current_date, $current_date, $current_date, $company_id ] );
		}
		public function getAdvancePayrollControl ( $employee_id, $user ): array {
			$query = "SELECT * FROM advancePayroll_control WHERE employee_id = $employee_id";
			//			var_dump ( $query );
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 47, 404, json_encode ( [ 'employee' => $employee_id ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$res = $res->getResultArray ();
			saveLog ( $user, 47, 200, json_encode ( [ 'employee' => $employee_id ] ), json_encode ( $res ) );
			return $res;
		}
		public function updateAdvancePayrollControl ( $id, $period_name, $days_worked, $amount_available, $available, $user ): BaseResult|array|bool|Query {
			$builder = $this->db->table ( 'advancePayroll_control' );
			$builder->where ( 'id', $id );
			$builder->update ( [
				'actual_period'    => $period_name,
				'worked_days'      => $days_worked,
				'amount_available' => $amount_available,
				'available'        => $available,
			] );
			$sqlQuery = $builder->getCompiledSelect ();
			if ( !$res = $this->db->query ( $sqlQuery ) ) {
				saveLog ( $user, 47, 404, json_encode ( [ 'args' => [ $id, $period_name, $amount_available, $days_worked ] ] ), json_encode ( [
					FALSE,
					'No se actualizo' ] ) );
				return [ FALSE, 'No se actualizo' ];
			}
			saveLog ( $user, 47, 200, json_encode ( [ 'args' => [ $id, $period_name, $amount_available, $days_worked ] ] ), json_encode ( $res ) );
			return $res;
		}
		public function insertAdvancePayrollControl ( $employee, $period_name, $days_worked, $amount_available, $available, $user ): Query|bool|array|BaseResult {
			$builder = $this->db->table ( 'advancePayroll_control' );
			$builder->insert ( [
				'employee_id'      => $employee,
				'actual_period'    => $period_name,
				'worked_days'      => $days_worked,
				'amount_available' => $amount_available,
				'available'        => $available,
			] );
			$sqlQuery = $builder->getCompiledSelect ();
			if ( !$res = $this->db->query ( $sqlQuery ) ) {
				saveLog ( $user, 47, 404, json_encode ( [ 'args' => [ $employee, $period_name, $amount_available, $days_worked ] ] ), json_encode ( [
					FALSE,
					'No se actualizo' ] ) );
				return [ FALSE, 'No se actualizo' ];
			}
			saveLog ( $user, 47, 200, json_encode ( [ 'args' => [ $employee, $period_name, $amount_available, $days_worked ] ] ), json_encode ( $res ) );
			return $res;
		}
		public function updateNomina ( $value, $company, $user ): bool {
			saveLog ( $user, 61, 200, json_encode ( [ 'args' => $value ] ), json_encode ( 'ok' ) );
			$query = "UPDATE person SET name = '{$value['Nombre']}', last_name = '{$value['Apellido paterno']}', sure_name = '{$value['Apellido materno']}', rfc = '{$value['RFC']}', active = '{$value['Estatus']}'
              WHERE id = '{$value['personId']}'";
			if ( $this->db->query ( $query ) ) {
				$neto = (float)str_replace ( [ '$', ',' ], '', $value[ 'Sueldo Neto' ] );
				$alta = date ( 'Y-m-d', strtotime ( $value[ 'Fecha de alta' ] ) );
				$query = "UPDATE employee SET external_id = '{$value['Número de empleado']}', status = '{$value['Estatus']}', hiring_date = '$alta', net_salary = '$neto'
                WHERE id =' {$value['employeeId']}' AND company_id = $company";
				//				var_dump ( $query );
				if ( $this->db->query ( $query ) ) {
					$query = "SELECT id FROM bank_accounts WHERE user_id = '{$value['userId']}' AND person_id = '{$value['personId']}' AND clabe = '{$value['Cuenta']}' AND company_id = $company";
					if ( $res = $this->db->query ( $query ) ) {
						if ( $res->getNumRows () < 1 ) {
							$query = "UPDATE bank_accounts SET active = 0 WHERE user_id = '{$value['userId']}' AND person_id = '{$value['personId']}' AND company_id = $company";
							if ( $this->db->query ( $query ) ) {
								$bank = $this->getBankByClave ( $value[ 'Cuenta' ] )[ 1 ];
								//								var_dump ($bank);die();
								$query = "INSERT INTO bank_accounts (user_id, person_id, company_id, bank_id,clabe, active, validated ) VALUES ( '{$value['userId']}', '{$value['personId']}', '$company','{$bank['id']}', '{$value['Cuenta']}', 1, 1)";
								if ( $this->db->query ( $query ) ) {
									return TRUE;
								}
							}
							return FALSE;
						}
						return TRUE;
					}
				}
			}
			return FALSE;
		}
		public function insertNomina ( $value, $company, $user ): void {
			
			if ( !isset( $value[ 'Nombre' ] ) || !isset( $value[ 'Cuenta' ] ) ||
			     $value[ 'Nombre' ] === '' || $value[ 'Cuenta' ] === '' ||
			     $value[ 'Nombre' ] === NULL || $value[ 'Cuenta' ] === NULL ) {
				saveLog ( $user, 62, 500, json_encode ( [ 'args' => $value ] ), json_encode ( 'Bad Data' ) );
				return;
			}
			$this->db->transStart ();
			// Insertar en la tabla `users`
			$userData = [
				'nickname' => 'user_'.uniqid (),
				'email'    => NULL,
				'password' => NULL,
				'active'   => $value[ 'Estatus' ],
			];
			//			var_dump ( $userData );
			$this->db->table ( 'users' )->insert ( $userData );
			$userId = $this->db->insertID ();
			// Insertar en la tabla `person`
			$personData = [
				'primary_user_id' => $userId,
				'name'            => strtoupper ( $value[ 'Nombre' ] ),
				'last_name'       => strtoupper ( $value[ 'Apellido paterno' ] ),
				'sure_name'       => strtoupper ( $value[ 'Apellido materno' ] ),
				'full_name'       => strtoupper ( trim ( $value[ 'Nombre' ].' '.$value[ 'Apellido paterno' ].' '.$value[ 'Apellido materno' ] ) ),
				'rfc'             => strtoupper ( $value[ 'RFC' ] ),
				'curp'            => strtoupper ( $value[ 'CURP' ] ),
				'active'          => $value[ 'Estatus' ],
			];
			$sql = "INSERT INTO person (primary_user_id, name, last_name, sure_name, full_name, rfc, curp, active)
VALUES (:primary_user_id:, :name:, :last_name:, :sure_name:, :full_name:, :rfc:, :curp:, :active:)
ON DUPLICATE KEY UPDATE
                     name = VALUES(name),
                     last_name = VALUES(last_name),
                     sure_name = VALUES(sure_name),
                     full_name = VALUES(full_name),
                     rfc = VALUES(rfc)";
			$this->db->query ( $sql, $personData );
			$personId = $this->db->query ( "SELECT id FROM person WHERE curp = :curp:", [ 'curp' => $personData[ 'curp' ] ] )->getRow ( 'id' );
			//			var_dump ($personData);
			// Insertar en `person_user`
			$this->db->table ( 'person_user' )->insert ( [
				'person_id' => $personId,
				'user_id'   => $userId,
			] );
			// Insertar en la tabla `employee`
			$neto = (float)str_replace ( [ '$', ',' ], '', $value[ 'Sueldo Neto' ] );
			$employeeData = [
				'company_id'  => $company, // Asigna un ID de empresa válido
				'person_id'   => $personId,
				'external_id' => $value[ 'Número de empleado' ],
				'hiring_date' => date ( 'Y-m-d', strtotime ( $value[ 'Fecha de alta' ] ) ),
				'net_salary'  => $neto,
				'status'      => $value[ 'Estatus' ],
				'plan'        => 'q',
			];
			$this->db->table ( 'employee' )->insert ( $employeeData );
			$employeeId = $this->db->insertID ();
			$this->db->table ( 'employee_user' )->insert ( [
				'employee_id' => $employeeId,
				'user_id'     => $userId,
			] );
			//			var_dump ($employeeData);
			// Insertar en la tabla `bank_accounts`
			$clabe = preg_replace ( '/\D/', '', $value[ 'Cuenta' ] );
			$bankId = $this->getBankByClave ( $clabe )[ 1 ][ 'id' ];
			$bankAccountData = [
				'person_id'  => $personId,
				'user_id'    => $userId,
				'company_id' => $company,
				'bank_id'    => $bankId,
				'clabe'      => $value[ 'Cuenta' ],
				'active'     => 1,
				'validated'  => 1,
			];
			$this->db->table ( 'bank_accounts' )->insert ( $bankAccountData );
			//			var_dump ($bankAccountData);
			// Insertar en `platform_access`
			$this->db->table ( 'platform_access' )->insert ( [
				'id_user'     => $userId,
				'id_platform' => 6,
				'active'      => 1,
			] );
			// Insertar en `permissions`
			$this->db->table ( 'permissions' )->insert ( [
				'user_id'  => $userId,
				'view_id'  => 3,
				'writable' => 1,
			] );
			$payRollControlData = [
				'employee_id'      => $employeeId,
				'available'        => 1,
				'amount_available' => $neto,
			];
			$this->db->table ( 'advancePayroll_control' )->insert ( $payRollControlData );
			saveLog ( $user, 62, 200, json_encode ( [ 'args' => $value ] ), json_encode ( [
				"user"     => $userId,
				"person"   => $personId,
				"employee" => $employeeId ] ) );
			$this->db->transComplete ();
		}
		public function getPayments ( $company ): array {
			$query = "SELECT c.id AS 'company_id', po.amount, po.concept, cBenef.short_name, ba.clabe, cb.magicAlias, po.noReference, po.folio, po.status, t.cep, po.death_line
FROM companies c
    INNER JOIN companies cBenef ON cBenef.id = 1
    INNER JOIN bank_accounts ba ON ba.company_id = cBenef.id AND ba.person_id IS NULL
    INNER JOIN cat_bancos cb ON cb.id = ba.bank_id
    INNER JOIN payments_order po ON po.company_id = c.id
    LEFT JOIN transactions t ON t.payments_id = po.id
WHERE c.id = $company";
			$res = $this->db->query ( $query )->getResultArray ();
			if ( !$res ) {
				/*saveLog ( $user, 46, 404, json_encode ( [ 'args' => [ $company_id, $current_date, $user ] ] ), json_encode ( [
					FALSE,
					'No se encontró información',
				] ) );*/
				return [ FALSE, 'No se encontró información' ];
			}
			return [TRUE, $res];
		}
	}
