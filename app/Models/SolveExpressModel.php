<?php
	
	namespace App\Models;
	
	use DateTime;
	use DateMalformedStringException as DateMalformedStringExceptionAlias;
	
	class SolveExpressModel extends BaseModel {
		public function getReport ( array $args ): array {
			$builder = $this->db->table ( 'advance_payroll t1' )
			                    ->select ( "p.name, p.last_name, p.sure_name, p.rfc, e.external_id, e.plan, e.net_salary,
              t1.requested_amount, t1.remaining_amount, t1.period, t2.noReference,
              t3.clabe, t3.card, t4.bnk_alias, t1.created_at as 'Fecha solicitud', t2.created_at as 'Ultima modificación'" )
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
			//			var_dump ($builder->getCompiledSelect ());
			//			die();
			$query = $builder->get ();
			if ( $query->getNumRows () > 0 ) {
				if ( $query->getNumRows () === 1 ) {
					return [ TRUE, $query->getResult ()[ 0 ] ];
				}
				return [ TRUE, $query->getResult () ];
			} else {
				return [ FALSE, 'No se encontraron resultados' ];
			}
		}
		public function getDashboard ( int $user ): array {
			$query = "SELECT p.name, p.last_name, p.sure_name, e.net_salary, e.plan, t1.amount_aviable, t1.worked_days, t1.aviable
    FROM advancePayroll_control t1
        INNER JOIN employee e ON e.id = t1.employee_id
        INNER JOIN person p ON p.id = e.person_id
        INNER JOIN users u ON u.id = p.user_id
        WHERE u.id = $user";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información' ];
			}
			$rows=  $res->getNumRows ();
			if ( $rows > 1 || $rows === 0 ) {
				return [ FALSE, 'No se encontró información' ];
			}
			return [ TRUE, $res->getResultArray ()[ 0 ] ];
		}
	}
