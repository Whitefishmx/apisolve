<?php
	
	namespace App\Controllers;
	
	use CodeIgniter\HTTP\ResponseInterface;
	use App\Models\ConciliacionModel;
	use App\Models\CfdiModel;
	use ZipArchive;
	use Exception;
	
	class Conciliaciones extends BaseController {
		private string $environment = 'SANDBOX';
		/**
		 * Function para cargar la información de archivo xml en tabla temporal para su procesamiento, devuelve las
		 * posibles conciliaciones que se pueden realizar
		 * @throws Exception
		 */
		public function uploadCFDIPlus (): ResponseInterface {
			$input = $this->getRequestInput ( $this->request );
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
						$user = $cfdi->createTmpInvoices ( $filesOk, $this->environment );
						$conciliaciones = [
							'conciliaciones' => $user[ 'conciliaciones' ],
							'error' => $filesErr,
						];
						if ( isset ( $user[ 'errors' ] ) ) {
							$conciliaciones[ 'db_errors' ] = $user[ 'errors' ];
						}
						return $this->getResponse ( $conciliaciones );
					} else {
						return $this->getResponse ( [
							'error' => 'No se logro abrir el archivo',
						], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR );
					}
				} else {
					return $this->getResponse ( [
						'error' => 'No es un archivo zip',
					], ResponseInterface::HTTP_BAD_REQUEST );
				}
			}
			return $this->getResponse ( [
				'error' => 'No se pudo cargar el archivo zip',
				'reasons' => $_FILES[ 'file' ][ 'error' ] ],
				ResponseInterface::HTTP_INTERNAL_SERVER_ERROR );
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
		 * Guarda los CFDI que si serán utilizados para la creación de una conciliación y genera las diferentes operaciones seleccionadas
		 * @return ResponseInterface
		 * @throws Exception
		 */
		public function chosenConciliation (): ResponseInterface {
			$input = $this->getRequestInput ( $this->request );
			$conciliations = $input[ 'conciliaciones' ];
			$user = $input[ 'user' ];
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
				$ids = $cfdi->savePermanentCfdi ( $conciliations, $this->environment );
				if ( empty( $ids ) ) {
					return $this->getResponse ( [
						'error' => 'No se pueden crear las conciliaciones',
						'reasons' => 'Error al guardar información de CFDI' ],
						ResponseInterface::HTTP_INTERNAL_SERVER_ERROR );
				}
				$ids[ 'client' ] = $user;
				$concilia = new ConciliacionModel();
				$ops = $concilia->makeConciliationPlus ( $ids, $this->environment );
				if ( empty( $ops ) ) {
					return $this->getResponse ( [
						'error' => 'No se pueden crear las conciliaciones',
						'reasons' => 'Error al guardar información de CFDI' ],
						ResponseInterface::HTTP_INTERNAL_SERVER_ERROR );
				}
				return $this->getResponse ( [ $ops ] );
			}
			return $this->getResponse ( [
				'error' => 'No se pueden crear las conciliaciones',
				'reasons' => 'No se selecciono ningún grupo a conciliar' ],
				ResponseInterface::HTTP_INTERNAL_SERVER_ERROR );
			
		}
	}