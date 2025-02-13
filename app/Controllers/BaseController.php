<?php /** @noinspection PhpDeprecationInspection */
	
	/** @noinspection PhpMultipleClassDeclarationsInspection */
	
	namespace App\Controllers;
	
	use CodeIgniter\Controller;
	use CodeIgniter\HTTP\CLIRequest;
	use JetBrains\PhpStorm\NoReturn;
	use CodeIgniter\HTTP\IncomingRequest;
	use CodeIgniter\HTTP\RequestInterface;
	use CodeIgniter\HTTP\ResponseInterface;
	use Psr\Log\LoggerInterface;
	
	abstract class BaseController extends Controller {
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
		public function logResponse ( int $function, ?array $inputData = NULL, ?array $responseData = NULL ): bool {
			return saveLog ( $this->user, $function, $this->errCode, json_encode ( $inputData ?? $this->input,
				JSON_UNESCAPED_UNICODE ), json_encode ( $responseData ?? $this->responseBody, JSON_UNESCAPED_UNICODE ) );
		}
		#[NoReturn] public function customThrow ( int $error, string $description, string $reason ): void {
			$this->responseBody = [ 'error' => $this->errCode = $error, 'description' => $description, 'reason' => $reason ];
			echo json_encode ( $this->responseBody );
			http_response_code ( $error );
			exit;
		}
		public function getResponse ( array $responseBody, ?int $code = NULL ): ResponseInterface {
			$code = $code === NULL ? $this->errCode : $code;
			return $this->response->setStatusCode ( $code )
			                      ->setJSON ( $responseBody )
			                      ->setHeader ( 'Content-Type', 'application/json' )
			                      ->setContentType ( 'application/json' );
		}
		/**
		 * @param IncomingRequest $request
		 *
		 * @return array|bool|float|int|mixed|object|string|null
		 */
		public function getRequestInput ( IncomingRequest $request ): mixed {
			$authenticationHeader = $request->getServer ( 'HTTP_AUTHORIZATION' );
			if ( !$authenticationHeader ) {
				$this->customThrow ( 401, 'Error de autenticación', 'Token no encontrado.' );
			}
			$encodedToken = getJWTFromRequest ( $authenticationHeader );
			if ( !$encodedToken ) {
				$this->customThrow ( 401, 'Error de autenticación', 'Token invalido.' );
			}
			$tokenData = validateJWTFromRequest ( $encodedToken );
			$this->user = $tokenData[ 1 ][ 'id' ];
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
			if ( empty( $input ) ) {
				$input = json_decode ( $request->getBody (), TRUE );
			}
			return $input;
		}
	}
