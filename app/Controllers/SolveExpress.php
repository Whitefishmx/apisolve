<?php
	
	namespace App\Controllers;
	
	use Exception;
	use App\Models\UserModel;
	use App\Models\MagicPayModel;
	use App\Models\SolveExpressModel;
	use App\Models\TransactionsModel;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class SolveExpress extends PagesStatusCode {
		private $express = '';
		public function __construct () {
			parent::__construct ();
			$this->express = new SolveExpressModel();
		}
		/**
		 * Permite generar un reporte y filtrar los resultados
		 * @return ResponseInterface
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
					'rfc'      => 'permit_empty|max_length[18]|regex_match[^[A-ZÑ&]{3,4}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[A-Z\d]{2}[A\d]$]',
					'name'     => 'permit_empty|alpha_space|max_length[150]|',
				],
				[ 'user' => [ 'max_length' => 'El id de usuario no debe tener mas de {param} caracteres' ] ] );
			if ( !$validation->run ( $this->input ) ) {
				$errors = $validation->getErrors ();
				$this->errDataSupplied ( $errors );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$express = new SolveExpressModel();
			$res = $express->getReport ( $this->input, intval ( $this->user ) );
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
			$res[ 1 ][ "min-available" ] = 250;
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
			$express = new SolveExpressModel();
			$res = $express->getDashboard ( intval ( $this->input[ 'user' ] ) );
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
			$rules = $this->cRules ( $this->input[ 'user' ] );
			if ( !$rules[ 0 ] ) {
				$this->serverError ( 'Error en el servicio', $rules[ 1 ] );
				$this->logResponse ( 15 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$order = $this->makeOrder ( $res[ 1 ] );
			if ( !$order[ 0 ] ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$transfer = $this->doTransfer ( $order[ 1 ], $res[ 1 ] );
			if ( !$transfer [ 0 ] ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$express->updateAvailableAmount ( $res[1]['employeeId'], floatval ($this->input[ 'amount' ]), $this->user);
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Solicitud procesada',
				'response'    => $transfer[ 1 ],
			];
			$this->logResponse ( 15 );
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
		 * @throws \DateMalformedStringException
		 */
		public function makeOrder ( $data ): array {
			$order = $this->express->generateOrder ( $this->user, floatval ( $this->input[ 'amount' ] ), floatval ( $data[ 'amount_available' ] ), $data[ 'plan' ] );
			if ( !$order[ 0 ] ) {
				$this->serverError ( 'Error en el servicio', 'Error al generar la petición, por favor intente nuevamente.' );
				$this->logResponse ( 15 );
				return [ FALSE, 'error' ];
			}
			return [ TRUE, $order[ 1 ] ];
		}
		public function doTransfer ( $order, $res ): array {
			$user = new userModel();
			$bank = $user->getBankAccountsByUser ( intval ( $this->user ) );
			if ( !$bank[ 0 ] ) {
				$this->serverError ( 'Error en el servicio', 'Error con la cuenta clabe' );
				$this->logResponse ( 15 );
				return [ FALSE, 'error' ];
			}
			//			die(var_dump ($order));
			$data = [
				'description'   => $order [ 'refNumber' ],
				'account'       => $bank[ 1 ][ 'clabe' ],
				//				'amount'        => $order[ 'amount' ],
				'amount'        => '0.01',
				'bank'          => $bank[ 1 ][ 'magicAlias' ],
				'owner'         => "{$res['name']} {$res['last_name']} {$res['sure_name']}",
				'validateOwner' => TRUE ];
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
			return [ TRUE, "El pago esta en proceso, el tiempo de espera dependerá de su banco" ];
		}
		public function updateOpTransaccionStatus ( array $transaction = NULL, array $operation = NULL ): void {
			
			if ($transaction !== NULL){
				$tModel = new TransactionsModel();
				$res = $tModel->updateTransactionStatus ($transaction['folio'], $transaction['noRef'], $transaction['status'], $this->user);
			}
			if ($operation !== NULL){
				$opModel = new updateOperationStatus();
				$res2 = $opModel->updateTransactionStatus ($operation['folio'], $operation['noRef'], $operation['status'], $this->user);
			}
		}
	}
