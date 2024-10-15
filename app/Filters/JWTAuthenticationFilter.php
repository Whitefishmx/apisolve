<?php
	
	namespace App\Filters;
	
	use CodeIgniter\API\ResponseTrait;
	use CodeIgniter\Filters\FilterInterface;
	use CodeIgniter\HTTP\RequestInterface;
	use CodeIgniter\HTTP\ResponseInterface;
	use Config\Services;
	
	class JWTAuthenticationFilter implements FilterInterface {
		use ResponseTrait;
		
		/**
		 * @param RequestInterface $request
		 * @param array|null       $arguments
		 *
		 * @return ResponseInterface|RequestInterface
		 * @throws \Exception
		 */
		public function before ( RequestInterface $request, $arguments = NULL ) {
			$authenticationHeader = $request->getServer ( 'HTTP_AUTHORIZATION' );
			helper ( 'jwt' );
//			$encodedToken = getJWTFromRequest ( $authenticationHeader );
//			var_dump (  validateJWTFromRequest ( $encodedToken ) );
//			die();
			try {
				$encodedToken = getJWTFromRequest ( $authenticationHeader );
				validateJWTFromRequest ( $encodedToken );
				return $request;
			} catch ( \Exception $e ) {
				return Services::response ()
				               ->setJSON ( [
					               'error' => $e->getMessage (),
				               ] )->setStatusCode ( ResponseInterface::HTTP_UNAUTHORIZED );
			}
		}
		/**
		 * Allows After filters to inspect and modify the response
		 * object as needed. This method does not allow any way
		 * to stop execution of other after filters, short of
		 * throwing an Exception or Error.
		 *
		 * @param RequestInterface  $request
		 * @param ResponseInterface $response
		 * @param array|null        $arguments
		 *
		 * @return void
		 */
		public function after ( RequestInterface $request, ResponseInterface $response, $arguments = NULL ) {
			//
		}
	}
