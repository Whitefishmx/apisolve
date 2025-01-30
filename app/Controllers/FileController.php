<?php
	
	namespace App\Controllers;
	
	use CodeIgniter\HTTP\DownloadResponse;
	use CodeIgniter\HTTP\ResponseInterface;
	use CodeIgniter\RESTful\ResourceController;
	use CodeIgniter\Exceptions\PageNotFoundException;
	
	class FileController extends ResourceController {
		public function downloadCEP ( $filename = NULL ): ResponseInterface|DownloadResponse|null {
			// Define la ruta donde están almacenados los archivos PDF
			$filePath = './public/boveda/CEP/'.$filename;
			// Verifica si el archivo existe
			if ( file_exists ( $filePath ) ) {
				// Forzar la descarga del archivo
				return $this->response->download ( $filePath, NULL )->setFileName ( $filename );
			} else {
				// Respuesta en caso de que el archivo no exista
				return $this->failServerError ( 'No se logro generar el archivo' );
			}
		}
		public function showCertBenefits ( $filename = NULL ): ResponseInterface|DownloadResponse|null {
			$filePath = './public/boveda/certs/'.$filename;
			if ( !file_exists ( $filePath ) ) {
				throw new PageNotFoundException( 'La imagen no existe.' );
			}
			$mimeType = mime_content_type ( $filePath );
			if ( !str_starts_with ( $mimeType, 'image/' ) ) {
				throw new PageNotFoundException( 'El archivo no es una imagen válida.' );
			}
			$imageData = file_get_contents ( $filePath );
			return $this->response->setContentType ( $mimeType )->setBody ( $imageData );
		}
		public function benefitsIco ( $filename = NULL ): ResponseInterface|DownloadResponse|null {
			$filePath = './public/assets/img/assistance_ico/'.$filename;
			if ( !file_exists ( $filePath ) ) {
				throw new PageNotFoundException( 'La imagen no existe.' );
			}
			$mimeType = mime_content_type ( $filePath );
			if ( !str_starts_with ( $mimeType, 'image/' ) ) {
				throw new PageNotFoundException( 'El archivo no es una imagen válida.' );
			}
			$imageData = file_get_contents ( $filePath );
			return $this->response->setContentType ( $mimeType )->setBody ( $imageData );
		}
		public function downloadCertBenefits ( $filename = NULL ): ResponseInterface|DownloadResponse|null {
			$filePath = './public/boveda/certs/'.$filename;
			if ( !file_exists ( $filePath ) ) {
				throw new PageNotFoundException( 'La imagen no existe.' );
			}
			return $this->response->download ( $filePath, NULL )->setFileName ( $filename );
		}
		public function downloadLayout ( $filename = NULL ): ResponseInterface|DownloadResponse|null {
			// Define la ruta donde están almacenados los archivos PDF
			$filePath = './public/boveda/layouts/'.$filename;
			// Verifica si el archivo existe
			if ( file_exists ( $filePath ) ) {
				// Forzar la descarga del archivo
				return $this->response->download ( $filePath, NULL )->setFileName ( $filename );
			} else {
				// Respuesta en caso de que el archivo no exista
				return $this->failServerError ( 'No se logro obtener el archivo' );
			}
		}
		public function generateCert ( $name, $folio, $inicio, $fin, $plan ): string|bool {
			$imagePath = './public/boveda/certs/';
			$namePosition = $vigenciaPosition = $folioPosition = '';
			if ( $plan === 1 ) {
				$imagePath .= 'MASTER.jpg';
				$namePosition = [ 108, 690 ];
				$vigenciaPosition = [ 108, 830 ];
				$folioPosition = [ 840, 830 ];
			} else if ( $plan === 2 ) {
				$imagePath .= 'PLUS.jpg';
				$namePosition = [ 108, 690 ];
				$vigenciaPosition = [ 108, 830 ];
				$folioPosition = [ 840, 830 ];
			} else if ( $plan === 3 ) {
				$imagePath .= 'BASIC.jpg';
				$namePosition = [ 108, 690 ];
				$vigenciaPosition = [ 108, 830 ];
				$folioPosition = [ 840, 830 ];
			}
			$image = imagecreatefromjpeg ( $imagePath );
			$black = imagecolorallocate ( $image, 0, 0, 0 );
			$fontPath = './public/assets/fonts/Poppins-Regular.ttf';
			$fontSize = 25;
			$vigencia = "$inicio / $fin";
			imagettftext ( $image, $fontSize, 0, $namePosition[ 0 ], $namePosition[ 1 ], $black, $fontPath, $name );
			imagettftext ( $image, $fontSize, 0, $vigenciaPosition[ 0 ], $vigenciaPosition[ 1 ], $black, $fontPath, $vigencia );
			imagettftext ( $image, $fontSize, 0, $folioPosition[ 0 ], $folioPosition[ 1 ], $black, $fontPath, $folio );
			$outputPath = "./public/boveda/certs/$folio.jpg";
			imagejpeg ( $image, $outputPath );
			imagedestroy ( $image );
			if ( !file_exists ( $outputPath ) ) {
				return FALSE;
			}
			return $folio.".jpg";
		}
	}