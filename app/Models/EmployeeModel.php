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
			$builder = $this->db->table ( 'users u' )
			                    ->select ( "u.id as userId, p.id as personId, e.id as employeeId, e.external_id,
            CapitalizarTexto(p.name) as 'name', CapitalizarTexto(p.last_name) as 'last_name', CapitalizarTexto(p.sure_name) as 'sure_name', p.curp,
           FORMAT_DATE( e.hiring_date) as 'hiringDate', FORMAT_DATE(e.fire_date) as 'fireDate',
            CONCAT('**** **** ****** ', SUBSTRING(ba.clabe, -4)) as 'clabe'" )
			                    ->join ( 'employee_user eu', 'u.id  = eu.user_id', 'INNER' )
			                    ->join ( 'employee e', 'e.id = eu.employee_id', 'INNER' )
			                    ->join ( 'person_user pu', 'u.id = pu.user_id', 'INNER' )
			                    ->join ( 'person p', 'pu.person_id = p.id', 'INNER' )
			                    ->join ( 'companies c', 'c.id = e.company_id', 'INNER' )
			                    ->join ( 'bank_accounts ba', 'ba.user_id = u.id AND ba.company_id = c.id AND ba.active = 1', 'INNER' )
			                    ->where ( 'c.id', $company )
			                    ->where ( 'u.type', 1 )
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
			if ( !$res = $this->db->query ( $sqlQuery ) ) {
				saveLog ( $user, 51, 404, json_encode ( [ 'args' => [ $company, $fire, $user ] ] ),
					json_encode ( $res->getResultArray (), TRUE ) );
				return [ FALSE, 'No se encontró información' ];
			}
			saveLog ( $user, 51, 200, json_encode ( [ 'args' => [ $company, $fire, $user ] ] ), json_encode (
				$res->getResultArray (), TRUE ) );
			return [ TRUE, $res->getResultArray () ];
		}
		public function fireEmployee ( int $employee, $company, int $user ): array {
			$query = "UPDATE employee SET status = 0, fire_date = NOW() WHERE id = $employee AND company_id = $company";
			if ( !$this->db->query ( $query ) ) {
				saveLog ( $user, 53, 500, json_encode ( [ 'employee' => $employee, 'company' => $company ] ),
					json_encode ( $this->db->affectedRows (), TRUE ) );
				return [ FALSE, 'No se pudo dar de baja al empleado' ];
			}
			saveLog ( $user, 53, 200, json_encode ( [ 'employee' => $employee, 'company' => $company ] ),
				json_encode ( $this->db->affectedRows (), TRUE ) );
			return [ TRUE, 'El empleado se dio de baja exitosamente' ];
		}
		public function fireEmployees ( array $employees, int $company, int $user ): array {
			$curp = implode ( ',', array_map ( fn( $value ) => "'".$this->db->escapeString ( $value )."'", $employees ) );
			$query = "UPDATE employee e INNER JOIN person p ON e.person_id = p.id
			SET e.status = 0, e.fire_date = NOW()
			WHERE p.curp IN ($curp)";
			if ( !$this->db->query ( $query ) ) {
				saveLog ( $user, 55, 500, json_encode ( [ 'employees' => $employees, 'company' => $company ] ),
					json_encode ( $this->db->affectedRows (), TRUE ) );
				return [ FALSE, 'No se pudieron dar de baja a los empleados' ];
			}
			saveLog ( $user, 55, 500, json_encode ( [ 'employees' => $employees, 'company' => $company ] ),
				json_encode ( $this->db->affectedRows (), TRUE ) );
			return [ TRUE, 'Los empleados se dieron de baja con éxito' ];
		}
	}