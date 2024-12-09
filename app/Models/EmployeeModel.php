<?php
	
	namespace App\Models;
	class EmployeeModel extends BaseModel {
		/**
		 * Retrieves a list of employees based on the specified criteria.
		 *
		 * @param int   $company The ID of the company to filter employees by.
		 * @param int   $fire    A flag indicating whether to include fired employees (0 for active only).
		 * @param array $args    An associative array of additional filters, including 'hiringDate', 'fireDate', 'rfc', 'curp', and 'name'.
		 * @param mixed $user    The user performing the query, used for logging purposes.
		 *
		 * @return array An array containing a boolean status and the result set, or an error message.
		 */
		public function getEmployees ( int $company, int $fire, array $args, mixed $user ): array {
			//			var_dump ($company, $fire, $user);die();
			$builder = $this->db->table ( 'employee e' )
			                    ->select ( "u.id as userId, p.id as personId, e.id as employeeId, e.external_id,
            p.name, p.last_name, p.sure_name, p.curp,
            e.hiring_date as 'hiringDate', e.fire_date as 'fireDate',
            CONCAT('**** **** ****** ', SUBSTRING(ba.clabe, -4)) as 'clabe'" )
			                    ->join ( 'person p', 'p.id = e.person_id', 'inner' )
			                    ->join ( 'person_user pu', 'p.id = pu.person_id', 'inner' )
			                    ->join ( 'users u', 'u.id = pu.user_id AND p.primary_user_id = u.id', 'inner' )
			                    ->join ( 'companies c', 'c.id = e.company_id', 'inner' )
			                    ->join ( 'bank_accounts ba', 'ba.user_id = u.id OR ba.person_id = p.id', 'left' )
			                    ->where ( 'c.id', $company )
			                    ->orderBy ( 'p.last_name', 'ASC' )->orderBy ( 'p.name', 'ASC' );
			if ( $fire === 0 ) {
				$builder->where ( 'e.status', 1 );
			}
			if ( !empty( $args[ 'hiringDate' ] ) ) {
				$builder->where ( 'e.hiring_date', $args[ 'hiringDate' ] );
			}
			if ( !empty( $args[ 'fireDate' ] ) ) {
				$builder->where ( 'e.fire_date', $args[ 'fireDate' ] );
			}
			if ( !empty( $args[ 'rfc' ] ) ) {
				$builder->like ( 'p.person', $args[ 'rfc' ] );
			}
			if ( !empty( $args[ 'curp' ] ) ) {
				$builder->like ( 'p.curp', $args[ 'curp' ] );
			}
			if ( !empty( $args[ 'name' ] ) ) {
				$builder->like ( 'p.full_name', $args[ 'name' ] );
			}
			$sqlQuery = $builder->getCompiledSelect ();
			//var_dump ( $sqlQuery);die();
			if ( !$res = $this->db->query ( $sqlQuery ) ) {
				saveLog ( $user, 51, 404, json_encode ( [ 'args' => [ $company, $fire, $user ] ] ),
					json_encode ( $res->getResultArray (), TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 51, 200, json_encode ( [ 'args' => [ $company, $fire, $user ] ] ), json_encode (
				$res->getResultArray (), TRUE ) );
			return [ TRUE, $res->getResultArray () ];
		}
		public function fireEmployee(int $employee, $company, int $user): array {
			$query = "UPDATE employee SET status = 0, fire_date = NOW() WHERE id = $employee AND company_id = $company";
            if (!$res = $this->db->query($query)) {
                saveLog($user, 53, 500, json_encode(['employee' => $employee, 'company' => $company]),
                    json_encode($this->db->affectedRows (), TRUE));
                return [FALSE, 'No se pudo dar de baja al empleado'];
            }
            saveLog($user, 53, 200, json_encode(['employee' => $employee, 'company' => $company]),
                json_encode($this->db->affectedRows (), TRUE));
            return [TRUE, 'El empleado se dio de baja exitosamente'];
		}
	}