<?php /** @noinspection PhpDeprecationInspection */
	
	/** @noinspection PhpMultipleClassDeclarationsInspection */
	
	namespace App\Controllers;
	
	use CodeIgniter\Controller;
	use CodeIgniter\HTTP\CLIRequest;
	use CodeIgniter\HTTP\IncomingRequest;
	use CodeIgniter\HTTP\RequestInterface;
	use CodeIgniter\HTTP\ResponseInterface;
	use Psr\Log\LoggerInterface;
	
	abstract class BaseController extends Controller {
		public string $env          = 'LIVE';
		public int    $user         = 2;
		public int    $errCode      = 200;
		public array  $responseBody = [];
		public mixed  $input        = NULL;
		/**
		 * Instance of the main Request object.
		 *
		 * @var CLIRequest|IncomingRequest
		 */
		protected $request;
		/**
		 * An array of helpers to be loaded automatically upon
		 * class instantiation. These helpers will be available
		 * to all other controllers that extend BaseController.
		 *
		 * @var array
		 */
		protected $helpers = [ 'tools_helper', 'jwt' ];
		public function __construct () {
			$this->responseBody = [
				'error'       => $this->errCode,
				'description' => 'Sesión invalida',
				'reason'      => 'la sesión a caducado, vuelve a iniciar sesión' ];
		}
		/**
		 * Be sure to declare properties for any property fetch you initialized.
		 * The creation of dynamic property is deprecated in PHP 8.2.
		 */
		// protected $session;
		/**
		 * @param RequestInterface  $request
		 * @param ResponseInterface $response
		 * @param LoggerInterface   $logger
		 *
		 * @return void
		 * @noinspection PhpMultipleClassDeclarationsInspection
		 */
		public function initController ( RequestInterface $request, ResponseInterface $response, LoggerInterface $logger ): void {
			// Do Not Edit This Line
			parent::initController ( $request, $response, $logger );
			// Preload any models, libraries, etc, here.
			// E.g.: $this->session = \Config\Services::session();
		}
		public function logResponse ( int $function, array $inputData = NULL, array $responseData = NULL ): bool {
			return saveLog ( $this->user, $function, $this->errCode, json_encode ( $inputData ?? $this->input,
				JSON_UNESCAPED_UNICODE ), json_encode ( $responseData ?? $this->responseBody, JSON_UNESCAPED_UNICODE ), $this->env );
		}
		public function getResponse ( array $responseBody, int $code = NULL ): ResponseInterface {
			$code = $code === NULL ? $this->errCode : $code;
			return $this->response->setStatusCode ( $code )->setJSON ( $responseBody )
				//			                      ->setHeader ( 'Access-Control-Allow-Origin', 'http://localhost:8081' )
				                  ->setHeader ( 'Content-Type', 'application/json' )
			                      ->setContentType ( 'application/json' );
		}
		/**
		 * @param IncomingRequest $request
		 *
		 * @return array|bool|float|int|mixed|object|string|null
		 * @throws \Exception
		 */
		public function getRequestInput ( IncomingRequest $request ): mixed {
//			var_dump ($request);
//			die();
			$authenticationHeader = $request->getServer ( 'HTTP_AUTHORIZATION' );
			$encodedToken = getJWTFromRequest ( $authenticationHeader );
			$tokenData = validateJWTFromRequest ($encodedToken);
//			var_dump ($tokenData );
//			die();
			$this->user = $tokenData[1]['id'];
			$method = strtolower ( $request->getMethod () );
			if ( $method === 'post' ) {
				$input = $request->getPost ();
			} else {
				$input = $request->getGet ();
			}
			if ( empty( $input ) ) {
				$input = json_decode ( $request->getBody (), TRUE );
			}
			return $input;
		}
		public function getRequestLogin ( IncomingRequest $request ): mixed {
			$method = strtolower ( $request->getMethod () );
			if ( $method === 'post' ) {
				$input = $request->getPost ();
			} else {
				$input = $request->getGet ();
			}
			if ( empty( $input ) ) {
				$input = json_decode ( $request->getBody (), TRUE );
			}
			return $input;
		}
		public function getGetRequestInput ( IncomingRequest $request ): mixed {
			$input = $request->getPostGet ();
			//			$input = $request->getPost ();
			if ( empty( $input ) ) {
				$input = json_decode ( $request->getBody (), TRUE );
			}
			return $input;
		}
		public function environment ( mixed $env ): void {
			$this->env = isset( $env[ 'environment' ] ) ? strtoupper ( $env[ 'environment' ] ) : 'SANDBOX';
		}
	}
