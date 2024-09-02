<?php
	
	namespace App\Controllers;
	
	use Config\Services;
	use CodeIgniter\HTTP\ResponseInterface;
	use DateTime;
	use CodeIgniter\Validation\Exceptions\ValidationException;
	
	class PagesStatusCode extends BaseController {
		/**
		 * Permite validar que el método y tipo de dato sean correctos al que solícita el recurso
		 *
		 * @param string      $method   Verbo requerido
		 * @param mixed       $request  Petición completa
		 * @param string|null $dataType Tipo de dato que se requiere
		 *
		 * @return array|bool
		 */
		public function verifyRules ( string $method, mixed $request, ?string $dataType ): array|bool {
			if ( !$request->is ( $method ) ) {
				return $this->methodNotAllowed ( $request->getPath () );
			}
			if ( !is_null ( $dataType ) ) {
				if ( !$request->is ( $dataType ) ) {
					return $this->dataTypeNotAllowed ( $dataType );
				}
			}
			return FALSE;
		}
		public function validateRequest ( $input, $rules, array $messages = [] ): bool {
			$this->validator = Services::validation ()->setRules ( $rules );
			if ( is_string ( $rules ) ) {
				$validation = config ( 'Validation' );
				if ( !isset( $validation->$rules ) ) {
					throw ValidationException::forRuleNotFound ( $rules );
				}
				if ( !$messages ) {
					$errorName = $rules.'_errors';
					$messages = $validation->$errorName ?? [];
				}
				$rules = $validation->$rules;
			}
			return $this->validator->setRules ( $rules, $messages )->run ( $input );
		}
		/**
		 * Valida si existe una session activa
		 * @return bool regresa true o false si esta una session activa
		 */
		public function validateSession (): bool {
			$session = session ();
			$login = $session->get ( 'logged_in' ) !== NULL ? $session->get ( 'logged_in' ) : FALSE;
			$session->set ( 'logged_in', $login );
			if ( $login ) {
				$this->user = $session->get ( 'user' );
			} else {
				$this->errCode = 500;
			}
			return $login;
		}
		public function serverError ( $description, $reason ): array {
			$this->errCode = 500;
			$this->responseBody = [ 'error' => $this->errCode, 'description' => $description, 'reason' => $reason ];
			return $this->responseBody;
		}
		public function dataTypeNotAllowed ( $dataType ): array {
			$this->errCode = 400;
			$this->responseBody = [
				'error'       => $this->errCode,
				'description' => 'Tipo de dato invalido',
				'reason'      => 'Se esperaba contenido en formato ['.$dataType.']' ];
			return $this->responseBody;
		}
		public function methodNotAllowed ( $endpoint ): array {
			$this->errCode = 405;
			$this->responseBody = [
				'error'       => $this->errCode,
				'description' => 'Método no implementado',
				'reason'      => 'El método utilizado no coincide con el que solicita ['.$endpoint.']' ];
			return $this->responseBody;
		}
		public function errDataSupplied ( $reason ): array {
			$this->errCode = 400;
			$this->responseBody = [
				'error'       => $this->errCode,
				'description' => 'Datos de petición incorrectos',
				'reason'      => $reason ];
			return $this->responseBody;
		}
		public function pageNotFound (): ResponseInterface {
			$this->errCode = 404;
			return $this->getResponse ( [
				'error'       => $this->errCode,
				'description' => 'Recurso no encontrada',
				'reason'      => 'Verifique que el endpoint sea correcto' ], ResponseInterface::HTTP_NOT_FOUND );
		}
		public function dataNotFound (): ResponseInterface {
			$this->errCode = 404;
			return $this->getResponse ( [
				'error'       => $this->errCode,
				'description' => 'Recurso no encontrada',
				'reason'      => 'No se encontró información con los datos ingresados' ], ResponseInterface::HTTP_NOT_FOUND );
		}
		public function redirectLogIn (): array {
			$this->errCode = 307;
			$this->responseBody = [
				'error'       => $this->errCode,
				'description' => 'Sesión invalida',
				'reason'      => 'la sesión a caducado, vuelve a iniciar sesión' ];
			return $this->responseBody;
		}
		/**
		 * Preparar las fechas para los filtros
		 *
		 * @param mixed  $input fecha de inicio y término
		 * @param string $from
		 * @param string $to
		 *
		 * @return array
		 */
		public function dateFilter ( mixed $input, string $from, string $to ): array {
			$from = DateTime::createFromFormat ( 'Y-m-d', $input[ $from ] );
			$to = DateTime::createFromFormat ( 'Y-m-d', $input[ $to ] );
			$from = strtotime ( $from->format ( 'm/d/y' ).' -1day' );
			$to = strtotime ( $to->format ( 'm/d/y' ).' +1day' );
			return [ $from, $to ];
		}
	}