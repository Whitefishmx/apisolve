<?php
	
	namespace App\Controllers;
	
	use CodeIgniter\HTTP\ResponseInterface;
	
	class PagesStatusCode extends BaseController {
		public function verifyRules ( string $dataType, string $method, $request ): ResponseInterface|bool {
			
			if ( !$request->is ( $method ) ) {
				return $this->methodNotAllowed ( 'stpTransactions' );
			}
			if ( !$request->is ( $dataType ) ) {
				return $this->dataTypeNotAllowed ( $dataType );
			}
			return FALSE;
		}
		public function pageNotFound (): ResponseInterface {
			return $this->getResponse ( [ 'error' => 404, 'description' => 'Recurso no encontrada', 'reason' => 'Verifique que el endpoint sea correcto' ], ResponseInterface::HTTP_NOT_FOUND );
		}
		public function methodNotAllowed ( $endpoint ): ResponseInterface {
			return $this->getResponse ( [ 'error' => 405, 'description' => 'Método no implementado', 'reason' => 'El método utilizado no coincide con el que solicita [' . $endpoint . ']' ], ResponseInterface::HTTP_METHOD_NOT_ALLOWED );
		}
		public function dataTypeNotAllowed ( $dataType ): ResponseInterface {
			return $this->getResponse ( [ 'error' => 400, 'description' => 'Tipo de dato invalido', 'reason' => 'Se esperaba contenido en formato [' . $dataType . ']' ], ResponseInterface::HTTP_BAD_REQUEST );
		}
		public function serverError ( $description, $reason ): ResponseInterface {
			return $this->getResponse ( [ 'error' => 500, 'description' => $description, 'reason' => $reason ], ResponseInterface::HTTP_BAD_REQUEST );
		}
	}