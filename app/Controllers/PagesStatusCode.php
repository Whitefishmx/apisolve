<?php
	
	namespace App\Controllers;
	
	use CodeIgniter\HTTP\ResponseInterface;
	use DateTime;
	
	class PagesStatusCode extends BaseController {
		public string $env = 'SANDBOX';
		/**
		 * Decide el ambiente en el que trabajaran las funciones, por defecto SANDBOX
		 *
		 * @param mixed $env Variable con el ambiente a trabajar
		 *
		 * @return void Asigna el valor a la variable global
		 */
		public function environment ( mixed $env ): void {
			$this->env = isset( $env[ 'environment' ] ) ? strtoupper ( $env[ 'environment' ] ) : 'SANDBOX';
		}
		/**
		 * Permite validar que el método y tipo de dato sean correctos al que solícita el recurso
		 *
		 * @param string      $method   Verbo requerido
		 * @param mixed       $request  Petición completa
		 * @param string|null $dataType Tipo de dato que se requiere
		 *
		 * @return ResponseInterface|bool
		 */
		public function verifyRules ( string $method, mixed $request, ?string $dataType ): ResponseInterface|bool {
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
		public function pageNotFound (): ResponseInterface {
			return $this->getResponse ( [ 'error' => 404, 'description' => 'Recurso no encontrada', 'reason' => 'Verifique que el endpoint sea correcto' ], ResponseInterface::HTTP_NOT_FOUND );
		}
		public function dataNotFound (): ResponseInterface {
			return $this->getResponse ( [ 'error' => 404, 'description' => 'Recurso no encontrada', 'reason' => 'No se encontró información con los datos ingresados' ], ResponseInterface::HTTP_NOT_FOUND );
		}
		public function methodNotAllowed ( $endpoint ): ResponseInterface {
			return $this->getResponse ( [ 'error' => 405, 'description' => 'Método no implementado', 'reason' => 'El método utilizado no coincide con el que solicita [' . $endpoint . ']' ], ResponseInterface::HTTP_METHOD_NOT_ALLOWED );
		}
		public function dataTypeNotAllowed ( $dataType ): ResponseInterface {
			return $this->getResponse ( [ 'error' => 400, 'description' => 'Tipo de dato invalido', 'reason' => 'Se esperaba contenido en formato [' . $dataType . ']' ], ResponseInterface::HTTP_BAD_REQUEST );
		}
		public function errDataSuplied ( $reason ): ResponseInterface {
			return $this->getResponse ( [ 'error' => 400, 'description' => 'Datos de petición incorrectos', 'reason' => $reason ], ResponseInterface::HTTP_BAD_REQUEST );
		}
		public function serverError ( $description, $reason ): ResponseInterface {
			return $this->getResponse ( [ 'error' => 500, 'description' => $description, 'reason' => $reason ], ResponseInterface::HTTP_BAD_REQUEST );
		}
		/**
		 * Preparar las fechas para los filtros
		 *
		 * @param mixed $input fecha de inicio y término
		 *
		 * @return array
		 */
		public function dateFilter ( mixed $input, string $from, string $to ): array {
			$from = DateTime::createFromFormat ( 'Y-m-d', $input[ $from ] );
			$to = DateTime::createFromFormat ( 'Y-m-d', $input[ $to ] );
			$from = strtotime ( $from->format ( 'm/d/y' ) . ' -1day' );
			$to = strtotime ( $to->format ( 'm/d/y' ) . ' +1day' );
			return [ $from, $to ];
		}
	}