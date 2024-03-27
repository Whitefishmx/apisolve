<?php
	
	namespace App\Controllers;
	
	use App\Controllers\BaseController;
	use App\Models\CfdiModel;
	use App\Models\UserModel;
	use CodeIgniter\HTTP\IncomingRequest;
	use CodeIgniter\HTTP\ResponseInterface;
	use ZipArchive;
	
	class Conciliaciones extends BaseController {
		private string $environment = 'SANDBOX';
		/**
		 * @throws \Exception
		 */
		public function uploadCFDIPlus () {
			$input = $this->getRequestInput ( $this->request );
			$company = json_decode ( base64_decode ( $input[ 'company' ] ), TRUE );
			$user = json_decode ( base64_decode ( $input[ 'user' ] ), TRUE );
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
							$validation = $this->validaComprobantePlus ( $doc, 1, $company, $user );
							if ( $validation[ 'code' ] === 200 ) {
								$filesOk[ $doc[ 'uuid' ] ] = $doc;
							} else {
								$filesErr [] = $validation;
							}
							unlink ( $file );
						};
						rmdir ( $extractedDir );
						$cfdi = new CfdiModel();
						$user = $cfdi->createTmpInvoices ( $filesOk, 'SANDBOX' );
						$conciliaciones = [
							'conciliaciones' => $user[ 'conciliaciones' ],
							'error' => $filesErr,
						];
						if ( isset ( $user[ 'errors' ] ) ) {
							$conciliaciones[ 'db_errors' ] = $user[ 'errors' ];
						}
						return $this->getResponse ( $conciliaciones, ResponseInterface::HTTP_OK );
					} else {
						$dato[ 'error' ] = "zip";
					}
				} else {
					return $this->getResponse ( [
						'error' => 'No es un archivo zip',
					], ResponseInterface::HTTP_BAD_REQUEST );
				}
			}
		}
		/**
		 * Función para validar el tipo de comprobante (Factura, Nora de débito)
		 * de acuerdo a las reglas de recepción para conciliaciones
		 *
		 * @param array       $factura Arreglo con los datos extraídos de factura_helper|XmlProcess
		 * @param int         $tipo    Tipo de comprobante que se validara: 1 = Factura | 2 = Nota de debito
		 * @param string|NULL $env     Ambiente en el que se trabajará “SANDBOX” | “LIVE”
		 * @param float|null  $monto   En caso de escoger tipo de documento 2 poner el monto de la factura a conciliar para su comparación
		 *
		 * @return array Devuelve el resultado de la validación con la descripcion caso de erro.
		 */
		public function validaComprobantePlus ( array $factura, int $tipo, array $company, array $user, string $env = NULL, float $monto = NULL ): array {
			//Se selecciona el ambiente a trabajar
			$env = $env === NULL ? $this->environment : $env;
			//Se verífica que el emisor de la factura sea el mismo que la compañía del usuario activo
			if ( ( $factura[ 'emisor' ][ 'rfc' ] === $company[ 'rfc' ] ) || ( $factura[ 'receptor' ][ 'rfc' ] === $company[ 'rfc' ] ) ) {
				return [
					'code' => 200,
					'reason' => 'Comprobante valido',
					'message' => 'OK',
				];
			}
			return [
				'uuid' => $factura[ 'uuid' ],
				'userOrigin' => $company[ 'rfc' ],
				'emisor' => $factura[ 'emisor' ][ 'rfc' ],
				'receptor' => $factura[ 'receptor' ][ 'rfc' ],
				'code' => 500,
				'reason' => 'RFC incorrecto',
				'message' => 'El RFC del emisor y receptor no coinciden con el que se registro para la empresa actual',
			];
		}
	}