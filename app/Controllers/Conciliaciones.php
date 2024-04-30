<?php
	
	namespace App\Controllers;
	
	use CodeIgniter\HTTP\ResponseInterface;
	use App\Models\ConciliacionModel;
	use App\Models\CfdiModel;
	use ZipArchive;
	use Exception;
	
	class Conciliaciones extends PagesStatusCode {
		private string $env = 'SANDBOX';
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
		 * Función para validar el tipo de comprobante (Factura, Nora de débito)
		 * de acuerdo a las reglas de recepción para conciliaciones
		 *
		 * @param array $factura Arreglo con los datos extraídos de factura_helper|XmlProcess
		 * @param array $company
		 *
		 * @return array Devuelve el resultado de la validación con la descripcion caso de erro.
		 */
		public function validaComprobantePlus ( array $factura, array $company ): array {
			//Sé verífica que el emisor de la factura sea el mismo que la compañía del usuario activo
			if ( ( $factura[ 'emisor' ][ 'rfc' ] === $company[ 'rfc' ] ) || ( $factura[ 'receptor' ][ 'rfc' ] === $company[ 'rfc' ] ) ) {
				return [
					'code' => 200,
					'reason' => 'Comprobante valido',
					'message' => 'OK',
				];
			}
			return [
				'tipo' => $factura[ 'tipo' ],
				'uuid' => $factura[ 'uuid' ],
				'userOrigin' => $company[ 'rfc' ],
				'emisor' => $factura[ 'emisor' ][ 'rfc' ],
				'receptor' => $factura[ 'receptor' ][ 'rfc' ],
				'code' => 500,
				'reason' => 'RFC incorrecto',
				'message' => 'El RFC del emisor y receptor no coinciden con el que se registro para la empresa actual',
			];
		}
		/**
		 * Function para cargar la información de archivo xml en tabla temporal para su procesamiento, devuelve las
		 * posibles conciliaciones que se pueden realizar
		 * @throws Exception
		 */
		public function uploadCFDIPlus (): ResponseInterface {
			if ( $data = $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			$company = json_decode ( base64_decode ( $input[ 'company' ] ), TRUE );
			if ( $_FILES[ 'file' ][ 'error' ] == UPLOAD_ERR_OK ) {
				$uploadedFile = $_FILES[ 'file' ];
				if ( pathinfo ( $uploadedFile[ 'name' ], PATHINFO_EXTENSION ) === 'zip' ) {
					$zip = new ZipArchive;
					if ( $zip->open ( $uploadedFile[ 'tmp_name' ] ) === TRUE ) {
						$extractedDir = './temporales/xml/';
						$zip->extractTo ( $extractedDir );
						$zip->close ();
						$xmlFiles = glob ( $extractedDir . '*.xml' );
						$filesErr = [];
						$filesOk = [];
						foreach ( $xmlFiles as $file ) {
							$xml = simplexml_load_file ( $file );
							helper ( 'factura' );
							$doc = XmlProcess ( $xml );
							$validation = $this->validaComprobantePlus ( $doc, $company );
							if ( $validation[ 'code' ] === 200 ) {
								$filesOk[ $doc[ 'uuid' ] ] = $doc;
							} else {
								$filesErr [] = $validation;
							}
							unlink ( $file );
						}
						rmdir ( $extractedDir );
						$cfdi = new CfdiModel();
						$user = $cfdi->createTmpInvoices ( $filesOk, $this->env );
						$conciliaciones = [
							'conciliaciones' => $user[ 'conciliaciones' ],
							'error' => $filesErr,
						];
						if ( isset ( $user[ 'errors' ] ) ) {
							$conciliaciones[ 'db_errors' ] = $user[ 'errors' ];
						}
						return $this->getResponse ( $conciliaciones );
					} else {
						return $this->serverError ( 'Proceso incompleto', 'No se logro abrir el archivo' );
					}
				} else {
					return $this->dataTypeNotAllowed ( '.zip' );
				}
			}
			return $this->serverError ( 'No se pudo cargar el archivo zip', $_FILES[ 'file' ][ 'error' ] );
		}
		/**
		 * Guarda los CFDI que si serán utilizados para la creación de una conciliación y genera las diferentes operaciones seleccionadas
		 * @return ResponseInterface
		 * @throws Exception
		 */
		public function chosenConciliation (): ResponseInterface {
			if ( $data = $this->verifyRules (  'POST', $this->request , NULL) ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			$conciliations = $input[ 'conciliaciones' ];
			$user = $input[ 'user' ];
			$company = $input[ 'company' ];
			if ( isset( $conciliations ) ) {
				$conciliations = explode ( ',', $conciliations );
				if ( count ( $conciliations ) > 0 ) {
					$item = [];
					foreach ( $conciliations as $row ) {
						$item[] = explode ( '|', $row );
					}
					$conciliations = $item;
				}
				$cfdi = new CfdiModel();
				$ids = $cfdi->savePermanentCfdi ( $conciliations, $this->env );
				if ( empty( $ids ) ) {
					return $this->serverError ( 'No se pueden crear las conciliaciones', 'Error al guardar información de CFDI' );
				}
				$ids[ 'client' ] = $user;
				$ids[ 'company' ] = $company;
				$concilia = new ConciliacionModel();
				$ops = $concilia->makeConciliationPlus ( $ids, $this->env );
				if ( empty( $ops ) ) {
					return $this->serverError ( 'No se pueden crear las conciliaciones', 'Error al guardar información de CFDI' );
				}
				return $this->getResponse ( [ $ops ] );
			}
			return $this->serverError ( 'No se pueden crear las conciliaciones', 'No se selecciono ningún grupo a conciliar' );
		}
		/**
		 * Regresa las conciliaciones plus de una empresa
		 * @return  bool|ResponseInterface con la información de las conciliaciones
		 * @throws Exception Errores
		 */
		public function getConciliationPlus (): ResponseInterface|bool {
			if ( $data = $this->verifyRules (  'POST', $this->request, 'JSON') ) {
				return ( $data );
			}
			$input = $this->getRequestInput ( $this->request );
			$this->environment ( $input );
			$company = $input[ 'company' ] ?? NULL;
			if ( $company === NULL ) {
				return $this->serverError ( 'Recurso no encontrada', 'Se esperaba el ID de la compañía a buscar' );
			}
			$conciliation = new ConciliacionModel();
			$res = $conciliation->getConciliationsPlus ( $company, $this->env );
			if ( !$res[ 0 ] ) {
				return $this->serverError ( 'Error proceso incompleto', $res[ 1 ] );
			}
			return $this->getResponse ( $res );
		}
	}