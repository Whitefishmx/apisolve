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
			if (!file_exists($filePath)) {
				throw new PageNotFoundException('La imagen no existe.');
			}
			$mimeType = mime_content_type($filePath);
			if (!str_starts_with($mimeType, 'image/')) {
				throw new \CodeIgniter\Exceptions\PageNotFoundException('El archivo no es una imagen válida.');
			}
			$imageData = file_get_contents($filePath);
			return $this->response->setContentType($mimeType)->setBody($imageData);
		}
		public function benefitsIco ( $filename = NULL ): ResponseInterface|DownloadResponse|null {
			$filePath = './public/assets/img/assistance_ico/'.$filename;
			if (!file_exists($filePath)) {
				throw new PageNotFoundException('La imagen no existe.');
			}
			$mimeType = mime_content_type($filePath);
			if (!str_starts_with($mimeType, 'image/')) {
				throw new \CodeIgniter\Exceptions\PageNotFoundException('El archivo no es una imagen válida.');
			}
			$imageData = file_get_contents($filePath);
			return $this->response->setContentType($mimeType)->setBody($imageData);
		}
		public function downloadCertBenefits ( $filename = NULL ): ResponseInterface|DownloadResponse|null {
			$filePath = './public/boveda/certs/'.$filename;
			if (!file_exists($filePath)) {
				throw new PageNotFoundException('La imagen no existe.');
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
	}