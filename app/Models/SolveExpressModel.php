<?php
	
	namespace App\Models;
	
	use DateTime;
	use MongoDB\Driver\Query;
	use DateMalformedStringException as DateMalformedStringExceptionAlias;
	
	class SolveExpressModel extends BaseModel {
		public function getReport ( array $args, int $user ): array {
			$builder = $this->db->table ( 'advance_payroll t1' )
			                    ->select ( "p.name, p.last_name, p.sure_name, p.rfc, e.external_id, e.plan, e.net_salary,
              t1.requested_amount, t1.remaining_amount, t1.period, t1.folio, t2.noReference,
              t3.clabe, t3.card, t4.bnk_alias, t1.created_at as 'Fecha_solicitud', t2.created_at as 'Ultima_modificación'" )
			                    ->join ( 'transactions t2', 't2.payroll_id = t1.id', 'left' )
			                    ->join ( 'bank_accounts t3', 't3.id = t2.account_destination', 'left' )
			                    ->join ( 'cat_bancos t4', 't4.id = t3.bank_id', 'inner' )
			                    ->join ( 'employee e', 't1.employee_id = e.id', 'inner' )
			                    ->join ( 'person p', 'e.person_id = p.id', 'inner' )
			                    ->join ( 'users u', 'p.user_id = u.id', 'inner' )
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
			if ( !empty( $args[ 'name' ] ) ) {
				$builder->like ( 'p.name', $args[ 'name' ] );
			}
			$sqlQuery = $builder->getCompiledSelect();
			if ( !$res = $this->db->query ( $sqlQuery ) ) {
				saveLog ( $user, 14, 404, json_encode ( [ 'args' => $args ] ), json_encode ( [
					FALSE,
					'No se encontró información' ] ) );
				return [ FALSE, 'No se encontró información' ];
			}
			$rows = $res->getNumRows ();
			if (  $rows > 0 ) {
				if ( $res->getNumRows () === 1 ) {
					saveLog ( $user, 12, 200, json_encode ( [ 'args' => $args ] ), json_encode ( $res->getResult ()[ 0 ] ) );
					return [ TRUE, $res->getResult ()[ 0 ] ];
				}
				$res = $res->getResultArray ();
				saveLog ( $user, 12, 200, json_encode (  [ 'args' => $args ] ), json_encode ( $res ) );
				return [ TRUE, $res ];
			} else {
				saveLog ( $user, 12, 404, json_encode (  [ 'args' => $args ] ), json_encode
				( [ 'res' => 'No se encontraron resultados' ] ) );
				return [ FALSE, 'No se encontraron resultados' ];
			}
		}
		public function getDashboard ( int $user ): array {
			$query = "SELECT p.name, p.last_name, p.sure_name, c.short_name, e.net_salary, e.plan, t1.amount_available, t1.worked_days, t1.available
    FROM advancePayroll_control t1
        INNER JOIN employee e ON e.id = t1.employee_id
        INNER JOIN person p ON p.id = e.person_id
        INNER JOIN users u ON u.id = p.user_id
        INNER JOIN companies c ON c.id = e.company_id
        WHERE u.id = $user";
			//			var_dump (json_encode ( [ 'query' => str_replace ("\n"," ",$query) ],JSON_UNESCAPED_UNICODE ));
			//			die();
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
			saveLog ( $user, 14, 200, json_encode ( [ 'query' => str_replace ( "\n", " ", $query ) ] ), json_encode (
				$res->getResultArray
				()[ 0 ], TRUE ) );
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
		public function getCompanyRules ( int $user ): array {
			$query = "SELECT r.*
FROM users u
    INNER JOIN person p ON p.user_id = u.id
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
    INNER JOIN person p ON p.user_id = u.id
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
			$refNumber = MakeOperationNumber ( $nexID );
			$employee = intval ( $this->getEmployee ( $user ) );
			$remaining = $preRemaining - $amount;
			$period = $this->getPeriod ( $plan );
			$dataIn = [ $employee, $folio, $refNumber, $amount, $remaining, $period ];
			$query = "INSERT INTO advance_payroll (employee_id, folio, reference_number, requested_amount, remaining_amount, period)
VALUES ($employee, '$folio', '$refNumber', $amount, $remaining, '$period')";
			$this->db->query ( 'SET NAMES utf8mb4' );
			if ( $this->db->query ( $query ) ) {
				saveLog ( $user, 19, 201, json_encode ( $dataIn ), json_encode ( [ 'id' => $this->db->insertId () ], TRUE ) );
				return [ TRUE, $this->db->insertId () ];
			} else {
				saveLog ( $user, 19, 400, json_encode ( $dataIn ), json_encode ( [
					FALSE,
					'No se pudo generar el pedido' ] ) );
				return [ FALSE, 'No se pudo generar el pedido' ];
			}
		}
		public function getEmployee ( int $user ): array {
			$query = "SELECT e.id FROM employee e INNER JOIN person p ON e.person_id = p.id INNER JOIN users u ON u.id = p.user_id WHERE u.id = $user";
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
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
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
					return "{$semanaDelMes}ª semana de {$mes} {$anio}";
				case 'q':
					if ( $dia <= 15 ) {
						return "1ª quincena de {$mes} {$anio}";
					} else {
						return "2ª quincena de {$mes} {$anio}";
					}
				case 'm':
					return "{$mes} {$anio}";
				default:
					return "Plan de pago no válido.";
			}
		}
	}
