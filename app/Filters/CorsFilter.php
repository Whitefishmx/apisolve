<?php
	
	namespace App\Filters;
	
	use CodeIgniter\Filters\FilterInterface;
	use CodeIgniter\HTTP\RequestInterface;
	use CodeIgniter\HTTP\ResponseInterface;
	
	class CorsFilter implements FilterInterface {
		public function after ( RequestInterface $request, ResponseInterface $response, $arguments = NULL ) {
		
		}
		public function before ( RequestInterface $request, $arguments = NULL ) {
			header('Access-Control-Allow-Origin: *'); // O puedes especificar un dominio en lugar de *
			header('Access-Control-Allow-Headers: Authorization, Content-Type, Origin, X-API-KEY, X-Requested-With, Accept, Access-Control-Request-Method, Access-Control-Allow-Headers, observe, enctype, Content-Length, X-Csrf-Token');
			header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, PATCH, OPTIONS');
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Max-Age: 3600');
			header ( 'content-type: application/json; charset=utf-8' );
			$method = $_SERVER[ 'REQUEST_METHOD' ];
			if ( $method == "OPTIONS" ) {
				header ( "HTTP/1.1 200 OK CORS" );
				die();
			}
//			var_dump ( $_SERVER );
//			die();
		}
	}