<?php
	
	namespace App\Controllers;
	use CodeIgniter\HTTP\DownloadResponse;
	use CodeIgniter\HTTP\ResponseInterface;
	use CodeIgniter\RESTful\ResourceController;
	
	class FileController extends ResourceController {
		public function downloadCEP($filename = null): ResponseInterface|DownloadResponse|null {
			// Define la ruta donde están almacenados los archivos PDF
			$filePath = './public/boveda/CEP/'. $filename;
			// Verifica si el archivo existe
			if (file_exists($filePath)) {
				// Forzar la descarga del archivo
				return $this->response->download($filePath, null)->setFileName($filename);
			} else {
				// Respuesta en caso de que el archivo no exista
				return $this->failServerError('No se logro generar el archivo');
			}
		}
		public function downloadLayout($filename = null): ResponseInterface|DownloadResponse|null {
			// Define la ruta donde están almacenados los archivos PDF
			$filePath = './public/boveda/layouts/'. $filename;
			// Verifica si el archivo existe
			if (file_exists($filePath)) {
				// Forzar la descarga del archivo
				return $this->response->download($filePath, null)->setFileName($filename);
			} else {
				// Respuesta en caso de que el archivo no exista
				return $this->failServerError('No se logro obtener el archivo');
			}
		}
	}