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
		 * Do whatever processing this filter needs to do.
		 * By default it should not return anything during
		 * normal execution. However, when an abnormal state
		 * is found, it should return an instance of
		 * CodeIgniter\HTTP\Response. If it does, script
		 * execution will end and that Response will be
		 * sent back to the client, allowing for error pages,
		 * redirects, etc.
		 *
		 * @param RequestInterface $request
		 * @param array|null       $arguments
		 *
		 * @return ResponseInterface|RequestInterface
		 */
		public function before ( RequestInterface $request, $arguments = NULL ) {
			try {
				if ( isset( $_SERVER[ 'HTTP_ORIGIN' ] ) ) {
					if ( $_SERVER[ 'HTTP_ORIGIN' ] === 'https://compensapay.local' && $_SERVER[ 'REMOTE_ADDR' ] === '127.0.0.1' ) {
						return $request;
					}
				} else if ( $_SERVER[ 'HTTP_USER_AGENT' ] === 'PostmanRuntime/7.37.0' && $_SERVER[ 'REMOTE_ADDR' ] === '127.0.0.1' ) {
					return $request;
				}
			} catch ( \Exception $e ) {
				$authenticationHeader = $request->getServer ( 'HTTP_AUTHORIZATION' );
				try {
					helper ( 'jwt' );
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
