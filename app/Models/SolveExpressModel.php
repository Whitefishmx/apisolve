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
FROM person p
    INNER JOIN employee e ON e.person_id  = p.id
    LEFT JOIN person_user pu ON pu.person_id = p.id
    LEFT JOIN users u ON pu.user_id = u.id
WHERE p.active = 1 and e.status = 1 AND p.curp = '$curp'";
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
		public function getReport ( array $args, int $user ): array {
			$builder = $this->db->table ( 'advance_payroll t1' )
			                    ->select ( "p.name, p.last_name, p.sure_name, p.rfc, p.curp, e.external_id, e.plan, e.net_salary,
              t1.requested_amount, t1.remaining_amount, t1.period, t1.folio, t2.noReference, t2.cep,
              t3.clabe, t3.card, t4.bnk_alias, t1.created_at as 'Fecha_solicitud', t2.created_at as 'Ultima_modificacion'" )
			                    ->join ( 'transactions t2', 't2.payroll_id = t1.id', 'left' )
			                    ->join ( 'bank_accounts t3', 't3.id = t2.account_destination', 'left' )
			                    ->join ( 'cat_bancos t4', 't4.id = t3.bank_id', 'inner' )
			                    ->join ( 'employee e', 't1.employee_id = e.id', 'inner' )
			                    ->join ( 'person p', 'e.person_id = p.id', 'inner' )
			                    ->join ( 'person_user pu', 'p.id = pu.person_id', 'inner' ) // Relación intermedia entre person y users
			                    ->join ( 'users u', 'pu.user_id = u.id', 'inner' )          // Relación entre person_user y users
			                    ->join ( 'companies c', 'e.company_id = c.id', 'inner' );
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
		public function getReportCompany ( array $args, int $user ): array {
			$builder = $this->db->table ( 'advance_payroll t1' )
			                    ->select ( "e.external_id, t1.employee_id , p.name, p.last_name, p.sure_name, p.rfc, p.curp, e.plan, e.net_salary,
SUM(t1.requested_amount) AS 'sum_request_amount', e.net_salary-SUM(t1.requested_amount) AS 'remaining_amount', t1.period" )
			                    ->join ( 'transactions t2', 't2.payroll_id = t1.id', 'INNER' )
			                    ->join ( 'bank_accounts t3', 't3.id = t2.account_destination', 'INNER' )
			                    ->join ( 'cat_bancos t4', 't4.id = t3.bank_id', 'inner' )
			                    ->join ( 'employee e', 't1.employee_id = e.id', 'inner' )
			                    ->join ( 'person p', 'e.person_id = p.id', 'inner' )
			                    ->join ( 'companies c', 'e.company_id = c.id', 'inner' );
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
			$builder->groupBy ( 't1.period' );
			$builder->groupBy ( 't1.employee_id' );
			$sqlQuery = $builder->getCompiledSelect ();
			//			var_dump ($sqlQuery );die();
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
		public function getReportCompanyV2 ( array $args, array $columns, int $user ): array {
			$builder = $this->db->table ( 'advance_payroll t1' );
			if ( in_array ( 'noEmpleado', $columns, FALSE ) ) {
				$builder->select ( "e.external_id AS '#Empleado'" );
			}
			if ( in_array ( 'name', $columns, FALSE ) ) {
				$builder->select ( "p.name AS 'Nombre'" );
			}
			if ( in_array ( 'lastName', $columns, FALSE ) ) {
				$builder->select ( "p.last_name AS 'Apellido Paterno'" );
			}
			if ( in_array ( 'sureName', $columns, FALSE ) ) {
				$builder->select ( "p.sure_name AS 'Apellido Materno'" );
			}
			if ( in_array ( 'rfc', $columns, FALSE ) ) {
				$builder->select ( "p.rfc AS 'RFC'" );
			}
			if ( in_array ( 'curp', $columns, FALSE ) ) {
				$builder->select ( "p.curp AS 'CURP'" );
			}
			if ( in_array ( 'plan', $columns, FALSE ) ) {
				$builder->select ( "CASE WHEN e.plan = 'q' THEN 'Quincenal' WHEN e.plan = 'm' THEN 'Mensual' WHEN plan = 's' THEN 'Semanal' ELSE 'Otro' END AS 'Esquema'" );
			}
			if ( in_array ( 'netSalary', $columns, FALSE ) ) {
				$builder->select ( "e.net_salary AS 'Salario Neto'" );
			}
			if ( in_array ( 'period', $columns, FALSE ) ) {
				$builder->select ( "t1.period AS 'Periodo'" );
			}
			$builder->select ( "SUM(t1.requested_amount) AS 'Total solicitado', e.net_salary-SUM(t1.requested_amount) AS 'Restante', apr.concept" )
			        ->join ( 'transactions t2', 't2.payroll_id = t1.id', 'INNER' )
			        ->join ( 'bank_accounts t3', 't3.id = t2.account_destination', 'INNER' )
			        ->join ( 'cat_bancos t4', 't4.id = t3.bank_id', 'INNER' )
			        ->join ( 'employee e', 't1.employee_id = e.id', 'INNER' )
			        ->join ( 'person p', 'e.person_id = p.id', 'INNER' )
			        ->join ( 'companies c', 'e.company_id = c.id', 'INNER' )
			        ->join ( 'advancePayroll_rules apr', 'apr.company_id = c.id', 'INNER' );
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
			$builder->groupBy ( 't1.period' );
			$builder->groupBy ( 't1.employee_id' );
			$builder->groupBy ( 'apr.concept ' );
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
       p.name, p.last_name, p.sure_name, c.short_name, e.net_salary, e.plan, t1.amount_available, t1.worked_days, t1.available,
       apr.min_amount, apr.max_amount, apr.commission, CONCAT('**** **** ****** ', SUBSTRING(ba.clabe, -4)) as 'clabe',
       req_day, req_biweekly, req_month
    FROM advancePayroll_control t1
        INNER JOIN employee e ON e.id = t1.employee_id
        INNER JOIN person p ON p.id = e.person_id
        INNER JOIN person_user pu ON p.id = pu.person_id
        INNER JOIN users u ON u.id = pu.user_id
        INNER JOIN companies c ON c.id = e.company_id
    	INNER JOIN advancePayroll_rules apr ON apr.company_id = c.id
        INNER JOIN bank_accounts ba ON ba.user_id  = u.id
        WHERE u.id = $user";
			//						var_dump ($query);
			//						die();
			if ( !$res = $this->db->query ( $query ) ) {
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
FROM users u
    INNER JOIN person_user pu ON u.id = pu.user_id
    INNER JOIN person p ON p.id = pu.person_id
    INNER JOIN employee e ON e.person_id = p.id
    INNER JOIN companies c ON c.id = e.company_id
    INNER JOIN advancePayroll_rules r ON r.company_id = c.id
    WHERE u.id = $user";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 17, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 17, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 17, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ), json_encode (
				$res->getResultArray ()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
		public function getControlByUSer ( int $user ): array {
			$query = "SELECT c.*
FROM users u
    INNER JOIN person_user pu ON u.id = pu.user_id
    INNER JOIN person p ON p.id = pu.person_id
    INNER JOIN employee e ON e.person_id = p.id
       INNER JOIN advancePayroll_control c ON c.employee_id = e.id
    WHERE u.id = $user";
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 18, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 18, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 18, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ), json_encode (
				$res->getResultArray ()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
		/**
		 * @throws DateMalformedStringExceptionAlias
		 */
		public function generateOrder ( int $user, float $amount, $preRemaining, $plan ): array {
			$folio = $this->generateFolio ( 19, 'advance_payroll', $user );
			$nexID = $this->getNexId ( 'advance_payroll' );
			$refNumber = $this->NewReferenceNumber ( $nexID );
			$employee = $this->getEmployee ( $user )[ 0 ][ 'id' ];
			$remaining = $preRemaining - $amount;
			$period = $this->getPeriod ( $plan );
			$dataIn = [ $employee, $folio, $refNumber, $amount, $remaining, $period ];
			$query = "INSERT INTO advance_payroll (employee_id, folio, reference_number, requested_amount, remaining_amount, period)
VALUES ($employee, '$folio', '$refNumber', $amount, $remaining, '$period')";
			$this->db->query ( 'SET NAMES utf8mb4' );
			if ( $this->db->query ( $query ) ) {
				$id = $this->db->insertId ();
				saveLog ( $user, 19, 200, json_encode ( $dataIn ), json_encode ( [ 'id' => $id ], TRUE ) );
				$dataOut = [ 'folio' => $folio, 'refNumber' => $refNumber, 'amount' => $amount - $this->commissions, 'payrollId' => $id ];
				return [ TRUE, $dataOut ];
			} else {
				saveLog ( $user, 19, 400, json_encode ( $dataIn ), json_encode ( [
					FALSE,
					'No se pudo generar el pedido' ] ) );
				return [ FALSE, 'No se pudo generar el pedido' ];
			}
		}
		public function getEmployee ( int $user ): array {
			$query = "SELECT e.id FROM employee e INNER JOIN person p ON e.person_id = p.id
    INNER JOIN person_user pu ON pu.person_id = p.id
    INNER JOIN users u ON u.id = pu.user_id WHERE u.id = $user";
			//var_dump ( $query);die();
			if ( !$res = $this->db->query ( $query ) ) {
				saveLog ( $user, 20, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				saveLog ( $user, 20, 404, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ),
					json_encode ( $res->getResultArray ()[ 0 ], TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 20, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ), json_encode (
				$res->getResultArray ()[ 0 ], TRUE ) );
			return [ $res->getResultArray ()[ 0 ] ];
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
		public function updateOperationStatus ( $folio, $noRef, $status, $user ): array {
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
		}
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
				$query = "SELECT * FROM payroll_periods
                  WHERE company_id = $company_id
                  AND start_date > '$current_date'
                  ORDER BY start_date
                  LIMIT 1";
				$res = $this->db->query ( $query )->getResultArray ();
			}
			if ( !$res ) {
				saveLog ( $user, 46, 404, json_encode ( [ 'args' => [ $company_id, $current_date, $user ] ] ), json_encode ( [
					FALSE,
					'No se encontró información',
				] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 46, 200, json_encode ( [ 'args' => [ $company_id, $current_date, $user ] ] ), json_encode ( $res ) );
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
	}
