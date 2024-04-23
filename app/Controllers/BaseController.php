<?php
	
	namespace App\Controllers;
	
	use CodeIgniter\Controller;
	use CodeIgniter\HTTP\CLIRequest;
	use CodeIgniter\HTTP\IncomingRequest;
	use CodeIgniter\HTTP\RequestInterface;
	use CodeIgniter\HTTP\ResponseInterface;
	use CodeIgniter\Validation\Exceptions\ValidationException;
	use Config\Services;
	use Psr\Log\LoggerInterface;
	
	abstract class BaseController extends Controller {
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
		protected $helpers = [];
		public function __construct () {
		}
		/**
		 * Be sure to declare properties for any property fetch you initialized.
		 * The creation of dynamic property is deprecated in PHP 8.2.
		 */
		// protected $session;
		/**
		 * @return void
		 */
		public function initController ( RequestInterface $request, ResponseInterface $response, LoggerInterface $logger ) {
			// Do Not Edit This Line
			parent::initController ( $request, $response, $logger );
			// Preload any models, libraries, etc, here.
			// E.g.: $this->session = \Config\Services::session();
		}
		public function getResponse ( array $responseBody, int $code = ResponseInterface::HTTP_OK ): ResponseInterface {
			return $this->response->setStatusCode ( $code )->setJSON ( $responseBody )->setHeader ( 'Access-Control-Allow-Origin', '*' );
		}
		/**
		 * @param IncomingRequest $request
		 *
		 * @return array|bool|float|int|mixed|object|string|null
		 */
		public function getRequestInput ( IncomingRequest $request ) {
			
			$input = $request->getPost ();
			if ( empty( $input ) ) {
				$input = json_decode ( $request->getBody (), TRUE );
			}
			return $input;
		}
		public function getHost ( IncomingRequest $request ): ResponseInterface {
			return $this->response->setStatusCode ( ResponseInterface::HTTP_OK )->setJSON ( $_SERVER )->setHeader ( 'Access-Control-Allow-Origin', '*' );
		}
		public function validateRequest ( $input, array $rules, array $messages = [] ): bool {
			$this->validator = Services::validation ()->setRules ( $rules );
			if ( is_string ( $rules ) ) {
				$validation = config ( 'Validation' );
				if ( !isset( $validation->$rules ) ) {
					throw ValidationException::forRuleNotFound ( $rules );
				}
				if ( !$messages ) {
					$errorName = $rules . '_errors';
					$messages = $validation->$errorName ?? [];
				}
				$rules = $validation->$rules;
			}
			return $this->validator->setRules ( $rules, $messages )->run ( $input );
		}
	}
