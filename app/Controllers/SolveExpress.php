<?php
	
	namespace App\Controllers;
	
	use DateTime;
	use Exception;
	use App\Models\{DataModel, EmployeeModel, UserModel, MagicPayModel, SolveExpressModel, TransactionsModel};
	use DateMalformedStringException;
	use CodeIgniter\HTTP\ResponseInterface;
	use PhpOffice\PhpSpreadsheet\IOFactory;
	use PhpOffice\PhpSpreadsheet\Style\Fill;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
	use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
	
	class SolveExpress extends PagesStatusCode {
		protected string|SolveExpressModel $express = '';
		public function __construct () {
			parent::__construct ();
			$this->express = new SolveExpressModel();
		}
		/**
		 * @throws Exception
		 */
		public function fireOne (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'DELETE', $this->request, 'JSON' ) ) {
				$this->logResponse ( 52 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$employeeM = new EmployeeModel();
			$res = $employeeM->fireEmployee ( $this->input[ 'employee' ], $this->input[ 'company' ], $this->user );
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error al cambiar el estatus del empleado', $res[ 1 ] );
				$this->logResponse ( 52 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Cambio de estatus correcto',
				'response'    => 'El empleado fue dado de baja con éxito',
			];
			$this->logResponse ( 52 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		/**
		 * @throws Exception
		 */
		public function getPeriods (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				$this->logResponse ( 36 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$express = new SolveExpressModel();
			$res = $express->getPeriods ( $this->input[ 'company' ], $this->user );
			if ( !$res[ 0 ] ) {
				$this->errCode = 404;
				$this->dataNotFound ();
				$this->logResponse ( 36 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Reporte generado correctamente',
				'response'    => $res[ 1 ],
			];
			$this->logResponse ( 36 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		/**
		 * @throws Exception
		 */
		public function dashboard (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 13 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$validation = service ( 'validation' );
			$validation->setRules (
				[
					'user' => 'required|permit_empty|max_length[7]|numeric',
				],
				[ 'user' => [ 'max_length' => 'El id de usuario no debe tener mas de {param} caracteres' ] ] );
			if ( !$validation->run ( $this->input ) ) {
				$errors = $validation->getErrors ();
				$this->errDataSupplied ( $errors );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$express = new SolveExpressModel();
			$res = $express->getDashboard ( intval ( $this->input[ 'user' ] ) );
			if ( !$res[ 0 ] ) {
				$this->errCode = 404;
				$this->dataNotFound ();
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			//			$res[ 1 ][ 'min_available' ] = 250;
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Updated Dashboard',
				'response'    => $res[ 1 ],
			];
			$this->logResponse ( 13 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		/**
		 * @throws Exception
		 */
		public function userProfile (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 49 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$user = new UserModel();
			$profile = $user->getExpressProfile ( $this->input[ 'user' ] );
			if ( !$profile[ 0 ] ) {
				$this->errCode = 404;
				$this->dataNotFound ();
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Perfil generado',
				'response'    => $profile[ 1 ],
			];
			$this->logResponse ( 49 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		/**
		 * @throws Exception
		 */
		public function verifyCurp (): ResponseInterface {
			$this->input = $this->getRequestLogin ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 37 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$express = new SolveExpressModel();
			$res = $express->verifyCurp ( $this->input[ 'curp' ] );
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error al validar CURP', $res[ 1 ] );
				$this->logResponse ( 37 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			if ( intval ( $res[ 1 ][ 'curp_validated' ] ) === 1 ) {
//								var_dump ($res[ 1 ][ 'device' ], $this->input[ 'fingerprint' ]); die();
				if ( $res[ 1 ][ 'device' ] !== $this->input[ 'fingerprint' ] ) {
					$this->serverError ( 'Dispositivo no reconocido', 'Ya se iniciado el proceso de validación desde otro dispositivo.' );
					return $this->getResponse ( $this->responseBody, $this->errCode );
				}
//				var_dump (intval ( $res[ 1 ][ 'metamap' ] ) === 1); die();
				if ( intval ( $res[ 1 ][ 'metamap' ] ) === 1 ) {
					$this->responseBody = [
						'error'       => $this->errCode = 200,
						'description' => 'CURP validada',
						'response'    => $res[ 1 ],
					];
					$this->logResponse ( 36 );
					return $this->getResponse ( $this->responseBody, $this->errCode );
				}
				$this->responseBody = [
					'error'       => $this->errCode = 202,
					'description' => 'CURP validada',
					'response'    => 'Aun no se termina de validar su identidad, por favor intente mas tarde',
				];
				$this->logResponse ( 36 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$update = $express->updateFlagCurp ( $res[ 1 ][ 'employee' ], $this->input[ 'fingerprint' ] );
			if ( !$update[ 1 ] ) {
				$this->serverError ( 'Error al validar CURP', $res[ 1 ] );
				$this->logResponse ( 37 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 201,
				'description' => 'CURP validada',
				'response'    => 'Por favor continue con el proceso de validación de identidad',
			];
			$this->logResponse ( 36 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function getPayments (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
//				$this->logResponse ( 50 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$company = $this->input[ 'company' ];
			$res = $this->express->getPayments ($company);
			if ( !$res[ 0 ] ) {
                $this->dataNotFound ();
                return $this->getResponse ( $this->responseBody, $this->errCode );
            }
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Pagos Obtenidos',
				'response'    => $res[1],
			];
			return $this->getResponse ($this->responseBody, $this->errCode);
		}
		/**
		 * @throws Exception
		 */
		public function uploadFires (): ResponseInterface|array {
			$file = $this->request->getFile ( 'bajas' );
			$this->input = $this->getRequestInput ( $this->request );
			//			var_dump ($file, $this->input);die();
			if ( !$file->isValid () ) {
				return $this->serverError ( 'No se cargó el archivo', 'No es un archivo válido' );
			}
			try {
				$spreadsheet = IOFactory::load ( $file->getTempName () );
				$sheetData = $spreadsheet->getActiveSheet ()->toArray ();
				$requiredHeaders = [ "CURP" ];
				$headers = array_map ( 'trim', $sheetData[ 0 ] );
				$headerIndices = [];
				foreach ( $requiredHeaders as $header ) {
					$index = array_search ( strtoupper ( $header ), $headers );
					$headerIndices[ $header ] = $index;
				}
				$curp = [];
				for ( $i = 1; $i < count ( $sheetData ); $i++ ) {
					$row = $sheetData[ $i ];
					foreach ( $headerIndices as $index ) {
						$value = $row[ $index ] ?? NULL;
						if ( $value !== NULL ) {
							$curp[] = $value;
						}
					}
				}
			} catch ( Exception $e ) {
				return $this->serverError ( 'Error al procesar el archivo', $e->getMessage () );
			}
			if ( empty( $curp ) ) {
				return $this->serverError ( 'Error al procesar el archivo', 'Por favor use la plantilla oficial y no cambie los encabezados' );
			}
			$employee = new EmployeeModel();
			$res = $employee->fireEmployees ( $curp, $this->input[ 'company' ], $this->user );
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error al cargar bajas', $res[ 1 ] );
				$this->logResponse ( 54, $this->input, $this->responseBody );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Bajas cargadas',
				'response'    => $res[ 1 ],
			];
			return $this->getResponse ( $this->responseBody );
		}
		/**
		 * @throws Exception
		 */
		public function getEmployees (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 50 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$fire = $this->input[ 'fire' ] ? 1 : 0;
			$employee = new EmployeeModel();
			$res = $employee->getEmployees ( $this->input[ 'company' ], $fire, $this->input, $this->user );
			if ( !$res[ 0 ] ) {
				$this->dataNotFound ();
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Empleados obtenidos',
				'response'    => $res[ 1 ],
			];
			$this->logResponse ( 50 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		/**
		 * @throws Exception
		 */
		public function requestAdvance (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 15 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$validation = service ( 'validation' );
			$validation->setRules (
				[
					'user'   => 'required|permit_empty|max_length[7]|numeric',
					'amount' => 'required|max_length[18]|greater_than_equal_to[0]|less_than_equal_to[4000]',
				],
				[ 'user' => [ 'max_length' => 'El id de usuario no debe tener mas de {param} caracteres' ] ] );
			if ( !$validation->run ( $this->input ) ) {
				$errors = $validation->getErrors ();
				$this->errDataSupplied ( $errors );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->user = $this->input[ 'user' ];
			$res = $this->express->getDashboard ( intval ( $this->input[ 'user' ] ) );
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error en el servicio', 'Por favor intente nuevamente o volver a iniciar sesión.' );
				$this->logResponse ( 15 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			if ( floatval ( $this->input[ 'amount' ] ) >= floatval ( $res[ 1 ][ 'amount_available' ] ) ) {
				$this->serverError ( 'Error en el servicio', "No se le puede adelantar mas de {$res[1]['amount_available']}" );
				$this->logResponse ( 15 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			if ( intval ( $res[ 1 ][ 'available' ] ) != 1 ) {
				$this->serverError ( 'Error en el servicio', "No cuenta con mas adelantos de nomina" );
				$this->logResponse ( 15 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->user = $this->input[ 'user' ];
			$rules = $this->cRules ( $this->input[ 'user' ] );
			if ( !$rules[ 0 ] ) {
				$this->serverError ( 'Error en el servicio', $rules[ 1 ] );
				$this->logResponse ( 15 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$order = $this->makeOrder ( $res[ 1 ], $this->input[ 'user' ], $res[ 1 ][ 'commission' ] );
			if ( !$order[ 0 ] ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$transfer = $this->doTransfer ( $order[ 1 ], $res[ 1 ] );
			if ( !$transfer [ 0 ] ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->express->updateAvailableAmount ( $res[ 1 ][ 'employeeId' ], floatval ( $this->input[ 'amount' ] ), $this->user );
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Solicitud éxitosa!.',
				'response'    => $transfer[ 1 ],
			];
			$this->logResponse ( 15 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function uploadNomina (): ResponseInterface|array {
			ini_set ( 'memory_limit', '1024M' );
			$file = $this->request->getFile ( 'nomina' );
			$this->input = $this->getRequestLogin ( $this->request );
			$this->user = $this->input[ 'user' ];
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				$this->logResponse ( 41, $this->input, $this->responseBody );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			/*helper ( 'crypt_helper' );
			$encryptionKey = getenv ( 'ENCRYPTION_KEY' );
			$ivSeedSearch = getenv ( 'SEARCH_SEED' );
			$ivSearch = generateIV ( $ivSeedSearch );*/
			if ( !$file->isValid () ) {
				return $this->serverError ( 'No se cargó el archivo', 'No es un archivo válido' );
			}
			try {
				$spreadsheet = IOFactory::load ( $file->getTempName () );
				$sheetData = $spreadsheet->getActiveSheet ()->toArray ();
				$requiredHeaders = [
					"Área",
					"Número de empleado",
					"Estatus",
					"RFC",
					"CURP",
					"Apellido paterno",
					"Apellido materno",
					"Nombre",
					"Fecha de alta",
					"Puesto funcional",
					"Confianza",
					"Sueldo base",
					"Sueldo Neto",
					"Banco",
					"Cuenta",
				];
				$headers = array_map ( 'trim', $sheetData[ 0 ] );
				$headerIndices = [];
				foreach ( $requiredHeaders as $header ) {
					$index = array_search ( $header, $headers );
					$headerIndices[ $header ] = $index;
				}
				$data = [];
				for ( $i = 1; $i < count ( $sheetData ); $i++ ) {
					$row = $sheetData[ $i ];
					$mappedRow = [];
					/*$ivSeed = $row[ $headerIndices[ 'Número de empleado' ] ] ?? 'default_seed';
					$iv = generateIV ( $ivSeed );*/
					foreach ( $headerIndices as $header => $index ) {
						$value = $row[ $index ] ?? NULL;
						if ( $header === "Estatus" || $header === "Confianza" ) {
							$mappedRow[ $header ] = ( strtoupper ( strtoupper ( $value ) ) === "X" ) ? 1 : 0;
						} else if ( $header === "Cuenta" ) {
							$clabe = preg_replace ( '/\D/', '', $value );
							$mappedRow[ $header ] = $clabe;
						} else if ( $header === "Fecha de alta" ) {
							$fechaFormateada = DateTime::createFromFormat ( 'd/m/Y', $value );
							if ( $fechaFormateada ) {
								$formated = $fechaFormateada->format ( 'Y-m-d' );
							} else {
								$formated = date ( 'Y-m-d', strtotime ( 'now' ) );
							}
							$mappedRow[ $header ] = $formated;
						} else if ( $header === "Sueldo Neto" || $header === "Sueldo base" ) {
							$sueldo = (float)str_replace ( [ '$', ',' ], '', $value );
							$mappedRow[ $header ] = $sueldo;
						} else if ( $header === "RFC" || $header === "CURP" || $header === "Nombre" || $header === 'Número de empleado' ) {
							/*$encryptedValue = encryptValue ( $value, $encryptionKey, $ivSearch );
							$mappedRow[ $header ] = $encryptedValue;*/
							$mappedRow[ $header ] = $value;
						} else {
							if ( $value !== NULL ) {
								/*$encryptedValue = encryptValue ( $value, $encryptionKey, $iv );
								$mappedRow[ $header ] = $encryptedValue;*/
								$mappedRow[ $header ] = $value;
							} else {
								$mappedRow[ $header ] = NULL;
							}
						}
						//						$mappedRow[ 'iv' ] = $iv;
					}
					$data[ $mappedRow[ 'CURP' ] ] = $mappedRow;
				}
			} catch ( Exception $e ) {
				return $this->serverError ( 'Error al procesar el archivo', $e->getMessage () );
			}
			if ( empty( $data ) ) {
				return $this->serverError ( 'Error al procesar el archivo', 'Por favor use la plantilla oficial y no cambie los encabezados' );
			}
			//			return $this->getResponse ( $data, 200 );
			$data = $this->checkExists ( $data, $this->input[ 'company' ] );
			//return $this->getResponse ( $data, 200 );
			$upsert = $this->upsertNomina ( $data, $this->input[ 'company' ], $this->user );
			try {
				$this->updateAdvancePayrollControl ( $this->input[ 'company' ] );
			} catch ( DateMalformedStringException $e ) {
				return $this->getResponse ( (array)$e, 200 );
			}
			return $this->getResponse ( $upsert, 200 );
		}
		private function checkExists ( $data, $company ): array {
			$userData = new UserModel();
			foreach ( $data as $value ) {
				$e = $userData->checkExistByCurp ( $value[ 'CURP' ], $company );
				if ( $e[ 0 ] ) {
					foreach ( $e[ 1 ] as $i => $v ) {
						$data[ $value[ 'CURP' ] ][ $i ] = ( $v );
					}
				}
			}
			return $data;
		}
		private function upsertNomina ( $data, $company, $user ): array {
			$exist = 0;
			$new = 0;
			foreach ( $data as $value ) {
				if ( isset ( $value[ 'personId' ] ) ) {
					$this->express->updateNomina ( $value, $company, $user );
					$exist++;
				} else {
					$this->express->insertNomina ( $value, $company, $user );
					$new++;
				}
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Nomina actualizada',
				'response'    => [ 'actualizaciones' => $exist, 'altas' => $new ] ];
			return [ $this->responseBody, $this->errCode ];
		}
		/**
		 * Permite generar un reporte y filtrar los resultados
		 * @return ResponseInterface
		 * @throws Exception
		 */
		public function initRecovery (): ResponseInterface {
			$this->input = $this->getRequestLogin ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 56 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$validation = service ( 'validation' );
			$validation->setRules (
				[
					'email' => 'required|valid_email',
				],
				[
					'email' => [
						'required'    => 'El campo {field} es obligatorio',
						'valid_email' => 'Por favor introduzca un correo valido', ] ] );
			if ( !$validation->run ( $this->input ) ) {
				$errors = $validation->getErrors ();
				$this->errDataSupplied ( $errors );
				$this->logResponse ( 56 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$user = new UserModel();
			$userData = $user->getUserByMail ( $this->input[ 'email' ] );
			if ( !$userData[ 0 ] ) {
				$this->dataNotFound ( 'Usuario no encontrado', 'La CURP que ingreso no esta registrada, contacte a Recursos Humanos.' );
				$this->logResponse ( 56 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			helper ( 'crypt_helper' );
			$code = getRecoveryCode ();
			if ( !$user->setRecoveryCode ( $code[ 'cypher' ], $userData[ 1 ][ 0 ][ 'userId' ] ) ) {
				$this->serverError ( 'No se logro actualizar los registros', 'No se logro generar el codigo de recuperación' );
				$this->logResponse ( 56 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$emailController = new Email();
			$name = $userData[ 1 ][ 0 ][ 'lastName' ]." ".$userData[ 1 ][ 0 ][ 'name' ];
			$emailResponse = $emailController->sendPasswordResetEmail ( $userData[ 1 ][ 0 ][ 'email' ], $code[ 'code' ], $name );
			if ( $emailResponse[ 'status' ] !== 'success' ) {
				$this->serverError ( 'No se logro actualizar los registros', 'No se logro enviar el codigo de recuperación' );
				$this->logResponse ( 56 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'CURP validada',
				'response'    => $userData[ 1 ][ 0 ][ 'userId' ],
			];
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function validateCode (): ResponseInterface {
			$this->input = $this->getRequestLogin ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 57 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$validation = service ( 'validation' );
			$validation->setRules (
				[
					'code' => 'required|exact_length[6]|regex_match[([\dA-Z]{6})]',
					'user' => 'required|max_length[7]|numeric',
				],
				[ 'user' => [ 'max_length' => 'El id de usuario no debe tener mas de {param} caracteres' ] ] );
			if ( !$validation->run ( $this->input ) ) {
				$errors = $validation->getErrors ();
				$this->errDataSupplied ( $errors );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$userdata = new UserModel();
			helper ( 'crypt_helper' );
			$codes = getPasswordCode ();
			$user = $userdata->validateRecoveryCode ( $this->input[ 'user' ], passwordEncrypt ( $this->input[ 'code' ] ), $codes[ 'cypher' ] );
			//			var_dump ($this->input[ 'user' ], passwordEncrypt($this->input[ 'code' ]), $codes[ 'cypher' ] );die();
			if ( !$user ) {
				$this->errCode = 404;
				$this->dataNotFound ( 'Datos incorrectos', 'El codigo que ingreso es incorrecto, inténtelo nuevamente, verifique su carpeta de spam' );
				$this->logResponse ( 57 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Codigo Correcto',
				'response'    => $codes[ 'code' ],
			];
			$this->logResponse ( 57 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		/**
		 * @throws Exception
		 */
		public function payrollAdvanceReport (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 11 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$validation = service ( 'validation' );
			$validation->setRules (
				[
					'user'     => 'permit_empty|max_length[7]|numeric',
					'employee' => 'permit_empty|max_length[7]',
					'company'  => 'permit_empty|max_length[7]|numeric',
					'initDate' => 'permit_empty|regex_match[\d{4}-\d{2}-\d{2}]',
					'endDate'  => 'permit_empty|regex_match[\d{4}-\d{2}-\d{2}]',
					'plan'     => 'permit_empty|max_length[1]|alpha',
					'rfc'      => 'permit_empty|max_length[18]',
					'curp'     => 'permit_empty|max_length[18]',
					'name'     => 'permit_empty|alpha_space|max_length[150]|',
				],
				[ 'user' => [ 'max_length' => 'El id de usuario no debe tener mas de {param} caracteres' ] ] );
			if ( !$validation->run ( $this->input ) ) {
				$errors = $validation->getErrors ();
				$this->errDataSupplied ( $errors );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$res = $this->express->getReport ( $this->input, $this->user );
			if ( !$res[ 0 ] ) {
				$this->errCode = 404;
				$this->dataNotFound ();
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Reporte generado correctamente',
				'response'    => $res[ 1 ],
			];
			$this->logResponse ( 11 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		/** @noinspection DuplicatedCode */
		/**
		 * @throws Exception
		 */
		public function excelFileReportCompany (): ResponseInterface|array {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 33 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$validation = service ( 'validation' );
			$validation->setRules (
				[
					'employee' => 'permit_empty|max_length[7]',
					'company'  => 'permit_empty|max_length[7]|numeric',
					'initDate' => 'permit_empty|regex_match[\d{4}-\d{2}-\d{2}]',
					'endDate'  => 'permit_empty|regex_match[\d{4}-\d{2}-\d{2}]',
					'plan'     => 'permit_empty|max_length[1]|alpha',
					'period'   => 'permit_empty|max_length[50]',
					'rfc'      => 'permit_empty|max_length[18]',
					'curp'     => 'permit_empty|max_length[18]',
					'name'     => 'permit_empty|alpha_space|max_length[150]|',
				],
				[ 'employee' => [ 'max_length' => 'El id de usuario no debe tener mas de {param} caracteres' ] ] );
			//			var_dump ( $this->request );
			if ( !$validation->run ( $this->input[ 'filters' ] ) ) {
				$errors = $validation->getErrors ();
				$this->errDataSupplied ( $errors );
				$this->logResponse ( 33 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$express = new SolveExpressModel();
			$res = $express->getReportCompanyV2 ( $this->input[ 'filters' ], $this->input[ 'columns' ], $this->user );
			//			var_dump ( $res );die();
			if ( !$res[ 0 ] ) {
				$this->errCode = 404;
				$this->dataNotFound ();
				$this->logResponse ( 33 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			try {
				// Crea el archivo Excel en memoria
				$spreadsheet = new Spreadsheet();
				$sheet = $spreadsheet->getActiveSheet ();
				$sheet->setTitle ( "ReporteNomina" );
				$headers = array_keys ( reset ( $res[ 1 ] ) );
				foreach ( $headers as $colIndex => $header ) {
					$column = Coordinate::stringFromColumnIndex ( $colIndex + 1 ); // Convierte índice numérico a columna (A, B, C...)
					$sheet->setCellValue ( $column.'1', $header );
					$sheet->getStyle ( $column.'1' )->applyFromArray (
						[
							'font' => [
								'bold'  => TRUE,
								'color' => [ 'rgb' => 'FFFFFF' ],
								'size'  => 12,
							],
							'fill' => [
								'fillType' => Fill::FILL_SOLID,
								'color'    => [ 'rgb' => '2A5486' ],
							],
						]
					);
					$sheet->getColumnDimension ( $column )->setAutoSize ( TRUE );
				}
				foreach ( $res[ 1 ] as $rowIndex => $row ) {
					foreach ( $headers as $colIndex => $header ) {
						$column = Coordinate::stringFromColumnIndex ( $colIndex + 1 );
						$sheet->setCellValue ( $column.( $rowIndex + 2 ), $row[ $header ] ?? '' ); // Usa '' si la clave no existe
					}
				}
				// Crea el escritor para la salida en memoria
				$writer = new Xlsx( $spreadsheet );
				ob_start ();
				$writer->save ( 'php://output' );
				$excelOutput = ob_get_clean ();
				helper ( 'tools_helper' );
				$name = month2Mes ( date ( 'm', strtotime ( 'now' ) ) )."_".date ( 'd_Y__H_i_s' );
				return $this->response
					->setContentType ( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' )
					->setHeader ( 'Content-Disposition', 'attachment; filename="'.$name.'.xlsx"' )
					->setBody ( $excelOutput );
			} catch ( Exception ) {
				// Manejo de errores en caso de fallo en la generación
				return $this->serverError ( 'No se pudo generar el archivo Excel ', 'Error al escribir el archivo' );
			}
		}
		/**
		 * @throws Exception
		 * @noinspection DuplicatedCode
		 */
		public function payrollAdvanceReportC (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 33 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$validation = service ( 'validation' );
			$validation->setRules (
				[
					'employee' => 'permit_empty|max_length[7]',
					'initDate' => 'permit_empty|regex_match[\d{4}-\d{2}-\d{2}]',
					'endDate'  => 'permit_empty|regex_match[\d{4}-\d{2}-\d{2}]',
					'plan'     => 'permit_empty|max_length[1]|alpha',
					'period'   => 'permit_empty|max_length[50]',
					'rfc'      => 'permit_empty|max_length[18]',
					'curp'     => 'permit_empty|max_length[18]',
					'name'     => 'permit_empty|alpha_space|max_length[150]|',
				],
				[ 'employee' => [ 'max_length' => 'El id de usuario no debe tener mas de {param} caracteres' ] ] );
			if ( !$validation->run ( $this->input ) ) {
				$errors = $validation->getErrors ();
				$this->errDataSupplied ( $errors );
				$this->logResponse ( 33 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$express = new SolveExpressModel();
			$res = $express->getReportCompany ( $this->input, $this->user );
//			var_dump (this->input);die();
			if ( !$res[ 0 ] ) {
				$this->errCode = 404;
				$this->dataNotFound ();
				$this->logResponse ( 33 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Reporte generado correctamente',
				'response'    => $res[ 1 ],
			];
			$this->logResponse ( 33 );
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function cRules ( int $user ): bool|array {
			$express = new SolveExpressModel();
			$rules = $express->getCompanyRules ( $user );
			$this->responseBody = [
				'response' => $rules,
			];
			$this->logResponse ( 16 );
			if ( !$rules[ 0 ] ) {
				return [ FALSE, 'Por favor intente nuevamente o volver a iniciar sesión.' ];
			}
			$control = $express->getControlByUSer ( $user );
			$this->responseBody = [
				'response' => $control,
			];
			$this->logResponse ( 16 );
			if ( !$control[ 0 ] ) {
				return [ FALSE, 'Por favor intente nuevamente o volver a iniciar sesión.' ];
			}
			if ( $control[ 1 ][ 'req_month' ] >= $rules[ 1 ][ 'limit_month' ] ) {
				return [ FALSE, 'Alcanzo el máximo de adelantos por mes permitidos para su empresa.' ];
			}
			if ( $control[ 1 ][ 'req_biweekly' ] >= $rules[ 1 ][ 'limit_biweekly' ] ) {
				return [ FALSE, 'Alcanzo el máximo de adelantos por quincena permitidos para su empresa.' ];
			}
			if ( $control[ 1 ][ 'req_week' ] >= $rules[ 1 ][ 'limit_week' ] ) {
				return [ FALSE, 'Alcanzo el máximo de adelantos por semana permitidos para su empresa.' ];
			}
			if ( $control[ 1 ][ 'req_day' ] >= $rules[ 1 ][ 'limit_day' ] ) {
				return [ FALSE, 'Alcanzo el máximo de adelantos por día permitidos para su empresa.' ];
			}
			return [ TRUE, 'ok' ];
		}
		/**
		 * @throws DateMalformedStringException
		 */
		public function makeOrder ( $data, $user, $commission ): array {
			$order = $this->express->generateOrder ( $user, floatval ( $this->input[ 'amount' ] ), floatval ( $data[ 'net_salary' ] ), $data[ 'plan' ], $commission,
				$data['actual_period'] );
			if ( !$order[ 0 ] ) {
				$this->serverError ( 'Error en el servicio', 'Error al generar la petición, por favor intente nuevamente.' );
				$this->logResponse ( 15 );
				return [ FALSE, 'error' ];
			}
			return [ TRUE, $order[ 1 ] ];
		}
		public function doTransfer ( $order, $res ): array {
			$user = new userModel();
			$bank = $user->getBankAccountsByUser ( $this->user );
			if ( !$bank[ 0 ] ) {
				$this->serverError ( 'Error en el servicio', 'Error con la cuenta clabe' );
				$this->logResponse ( 15 );
				return [ FALSE, 'error' ];
			}
			$data = [
				'description'   => $order [ 'refNumber' ],
				'account'       => $bank[ 1 ][ 'clabe' ],
				'amount'        => $order[ 'amount' ],
				//				'amount'        => '0.01',
				'bank'          => $bank[ 1 ][ 'magicAlias' ],
				'owner'         => "{$res['name']} {$res['last_name']} {$res['sure_name']}",
				'validateOwner' => FALSE ];
			$magic = new MagicPayModel();
			$transfer = $magic->createTransfer ( $data, $order[ 'refNumber' ], $order[ 'folio' ] );
			if ( !$transfer[ 0 ] ) {
				$this->serverError ( 'Error al crear la transferencia', 'No se pudo realizar la transacción' );
				return [ FALSE, 'error' ];
			}
			$bankO = $user->getBankAccountsByUser ( 1 );
			//			die(var_dump ($transfer));
			$transferData = [
				'opId'          => $order[ 'payrollId' ],
				'transactionId' => $transfer[ 1 ][ 'speiId' ],
				'description'   => $order[ 'refNumber' ],
				'noReference'   => $order[ 'refNumber' ],
				'amount'        => $order[ 'amount' ],
				'destination'   => $bank[ 1 ][ 'id' ],
				'origin'        => $bankO[ 1 ][ 'id' ], ];
			$transaction = new TransactionsModel();
			$save = $transaction->insertTransaction ( 'payroll_id', $transferData, $this->user );
			if ( !$save[ 0 ] ) {
				$this->serverError ( 'Error al crear la transferencia', 'No se pudo realizar la transacción' );
				return [ FALSE, 'error' ];
			}
			return [ TRUE, "Hemos transferido el monto; el tiempo de espera puede variar según su banco." ];
		}
		public function ExpressWH (): ResponseInterface|bool|array {
			$this->user = 20;
			$this->input = $this->getRequestLogin ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, 'JSON' ) ) {
				$this->logResponse ( 41, $this->input, $this->responseBody );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->errCode = 200;
			$out = [
				'error'       => $this->errCode,
				'description' => 'Información recibida.',
				'reason'      => 'Los datos se recibieron y procesaron con éxito.' ];
			if ( !$this->logResponse ( 41, $this->input, $out ) ) {
				$this->serverError ( 'Proceso incompleto', 'No se logró guardar la información' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$data = json_decode ( json_encode ( $this->input ), TRUE );
			if ( isset ( $data [ 'step' ][ 'id' ] ) ) {
				if ( $data [ 'step' ][ 'id' ] === 'facematch' ) {
					$this->faceMatch ( $data, $this->user );
				}
				//				if ( $data [ 'step' ][ 'id' ] === 'document-reading' ) {
				//					$upDateData = [
				//						'name' => $data[ 'step' ][ 'data' ][ 'firstName' ][ 'value' ] ];
				//				}
			}
			$this->responseBody = $out;
			return $this->getResponse ( $this->responseBody );
		}
		private function faceMatch ( $data, $user ): void {
			$score = intval ( $data[ 'step' ][ 'data' ][ 'score' ] > 60 );
			$sExpress = new SolveExpressModel();
			$sExpress->updateMetaValidation ( $data[ 'metadata' ][ 'curp' ], $score, $user );
			//SendNotification
		}
		/**
		 * @throws DateMalformedStringException
		 */
		public function updateAdvancePayrollControl ( $company ): void {
			$current_date = date ( 'Y-m-d', strtotime ( 'now' ) );
			echo $current_date.' - ';
			$dataM = new DataModel();
			$company_id = $company;
			echo 'company: '.$company_id.PHP_EOL;
			$employees = $dataM->getEmployeesFromCompany ( $company_id, $this->user );
			$actual = 0;
			$total_employees = count ( $employees );
			$periodActual = '';
			foreach ( $employees as $employee ) {
				$period = $this->express->getPeriodsCompany ( $company_id, $current_date, $this->user );
				$plan = $employee[ 'plan' ];
				$net_salary = $employee[ 'net_salary' ];
				$start_date = new DateTime( $period[ 'start_date' ] );
				$current = new DateTime( $current_date );
				$period_name = $this->generatePeriodName ( $period[ 'start_date' ], $period[ 'end_date' ], $period[ 'cutoff_date' ], $period[ 'payment_date' ], $plan, $current_date );
				$days_worked = $current->diff ( $start_date )->days + 1;
				$total_requests = $this->express->getSumRequest ( $employee, $period_name );
				$days_in_month = cal_days_in_month ( CAL_GREGORIAN, date ( 'm', strtotime ( $current_date ) ), date ( 'Y', strtotime ( $current_date ) ) );
				$amount_available = ( ( ( $net_salary / $days_in_month ) * $days_worked ) * 0.8 ) - $total_requests;
				if ( $current_date === $period[ 'cutoff_date' ] ) {
					$available = 0;
				} else {
					$available = 1;
				}
				$existing = $this->express->getAdvancePayrollControl ( $employee[ 'id' ], $this->user )[ 0 ];
				if ( $existing ) {
					$this->express->updateAdvancePayrollControl ( $existing[ 'id' ], $period_name, $days_worked, $amount_available, $available, $this->user );
				} else {
					$this->express->insertAdvancePayrollControl ( $employee[ 'id' ], $period_name, $days_worked, $amount_available, $available, $this->user );
				}
				$periodActual = $period[ 'start_date' ].' - '.$period[ 'end_date' ].' - '.$period_name.' - '.$days_worked;
				$actual++;
				echo date ( 'm-d-Y H:i:s' ).' - '.$actual.' empleado(s) de '.$total_employees.PHP_EOL;
			}
			echo $periodActual.PHP_EOL;
			echo '================================================================'.PHP_EOL;
			$this->express->resetCounters ( $company_id, $current_date );
		}
		private function getMonthName ( $month ): string {
			$months = [
				1  => 'Enero',
				2  => 'Febrero',
				3  => 'Marzo',
				4  => 'Abril',
				5  => 'Mayo',
				6  => 'Junio',
				7  => 'Julio',
				8  => 'Agosto',
				9  => 'Septiembre',
				10 => 'Octubre',
				11 => 'Noviembre',
				12 => 'Diciembre',
			];
			return $months[ $month ] ?? '';
		}
		/**
		 * @throws DateMalformedStringException
		 */
		private function generatePeriodName ( $start_date, $end_date, $cutoff_date, $payment_date, $plan, $current_date ): string {
			$start = new DateTime( $start_date );
			$end = new DateTime( $end_date );
			$pay = new DateTime( $payment_date );
			new DateTime( $cutoff_date );
			$current = new DateTime( $current_date );
			$month = $this->getMonthName ( $end->format ( 'n' ) );
			$year = $end->format ( 'Y' );
			switch ( strtoupper ( $plan ) ) {
				case 'Q': // Quincenal
					// Verificar si la fecha actual está dentro del rango de este periodo
					if ( $current >= $start && $current <= $end ) {
						// Determinar si es 1ª o 2ª quincena del mes basándose en el día de inicio
						if ( (int)$pay->format ( 'd' ) <= 15 ) {
							return "1ª quincena de $month $year";
						} else {
							return "2ª quincena de $month $year";
						}
					} else {
						// Si la fecha actual no está dentro del rango, asumimos que corresponde al siguiente periodo
						$next_month = $this->getMonthName ( $end->format ( 'n' ) );
						$next_year = $end->format ( 'Y' );
						if ( (int)$end->format ( 'd' ) <= 15 ) {
							return "1ª quincena de $next_month $next_year";
						} else {
							return "2ª quincena de $next_month $next_year";
						}
					}
				case 'S': // Semanal
					return "Semana del {$start->format('d')} al {$end->format('d')} de $month $year";
				case 'C': // Catorcenal
					return "Catorcena del {$start->format('d')} al {$end->format('d')} de $month $year";
				case 'M': // Mensual
					return "Mes de $month $year";
				default:
					return "Periodo del {$start->format('d')} al {$end->format('d')} de $month $year";
			}
		}
		/*			public function updateOpTransaccionStatus ( array $transaction = NULL, array $operation = NULL ): void {
				if ( $transaction !== NULL ) {
					$tModel = new TransactionsModel();
					$res = $tModel->updateTransactionStatus ( $transaction[ 'folio' ], $transaction[ 'noRef' ], $transaction[ 'status' ], $this->user );
				}
				if ( $operation !== NULL ) {
					$opModel = new updateOperationStatus();
					$res2 = $opModel->updateTransactionStatus ( $operation[ 'folio' ], $operation[ 'noRef' ], $operation[ 'status' ], $this->user );
				}
			}*/
	}