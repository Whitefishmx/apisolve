<?php
	
	namespace App\Models;
	class MagicPayModel extends BaseModel {
		private array $apiKey = [
			//			'development' => 'sk_live_39kdOyJtKEih1XOwTUFlNoJsFYNJo11v',
			'development' => 'sk_live_jMMnyG9ZrqKPqCd9SjKzMIdI41ecI7ex',
			'production'  => 'sk_prod_39kdOyJtKEih1XOwTUFlNoJsFYNJo11v' ];
		public function getBalance (): bool|array {
			$env = getenv ( 'CI_ENVIRONMENT' );
			$data = [ 'apiKey' => $this->apiKey[ strtolower ( $env ) ] ];
			$res = $this->sendRequest ( 'getBalance', $data, 'POST', 'JSON', NULL );
			saveLog ( 2, 6, $res[ 'code' ], json_encode ( $data ), $res[ 'response' ] );
			if ( !$res[ 0 ] ) {
				return FALSE;
			}
			return [ TRUE, 'response' => $res[ 'response' ] ];
		}
		public function getTransfers ( array $args ): bool|array {
			$env = getenv ( 'CI_ENVIRONMENT' );
			$data = [ 'apiKey' => $this->apiKey[ strtolower ( $env ) ] ];
			$fields = [
				'status' => 'status',
				'before' => fn( $val ) => strtotime ( $val ),
				'after'  => fn( $val ) => strtotime ( $val ),
				'skip'   => fn( $val ) => intval ( $val ),
				'limit'  => fn( $val ) => intval ( $val ),
			];
			foreach ( $fields as $key => $transform ) {
				if ( isset( $args[ $key ] ) ) {
					$data[ $key ] = is_callable ( $transform ) ? $transform( $args[ $key ] ) : $args[ $key ];
				}
			}
			$res = $this->sendRequest ( 'getTransfers', $data, 'POST', 'JSON', NULL );
			saveLog ( 2, 7, $res[ 'code' ], json_encode ( $data ), $res[ 'response' ] );
			if ( !$res[ 0 ] ) {
				return FALSE;
			}
			return [ TRUE, 'response' => $res[ 'response' ] ];
		}
		public function getTransfersByID ( string $id ): bool|array {
			$env = getenv ( 'CI_ENVIRONMENT' );
			$data = [ 'apiKey' => $this->apiKey[ strtolower ( $env ) ], 'transferId' => $id ];
			$res = $this->sendRequest ( 'getTransfer', $data, 'POST', 'JSON', NULL );
			saveLog ( 2, 9, $res[ 'code' ], json_encode ( $data ), $res[ 'response' ] );
			if ( !$res[ 0 ] ) {
				return FALSE;
			}
			return [ TRUE, 'response' => $res[ 'response' ] ];
		}
		public function createTransfer ( array $args, $referenceNum = NULL, string $folio = NULL, $user = NULL ): bool|array {
			$user = $user === NULL ? 2 : $user;
			$env = getenv ( 'CI_ENVIRONMENT' );
			if ( $referenceNum === NULL ) {
				helper ( 'tools_helper' );
				$referenceNum = MakeOperationNumber ( $this->getNexId ( 'logs' ) );
			}
			if ( $folio === NULL ) {
				helper ( 'tetraoctal_helper' );
				$folio = $this->generateFolio ( 10, 'logs', 2 );
			}
			$data = [
				'apiKey'        => $this->apiKey[ strtolower ( $env ) ],
				'transferId'    => "$folio",
				'description'   => $args[ 'description' ],
				'account'       => $args[ 'account' ],
				'numReference'  => "$referenceNum",
				'amount'        => floatval ( $args[ 'amount' ] ),
				'bank'          => $args[ 'bank' ],
				'owner'         => $args[ 'owner' ],
				'validateOwner' => $args[ 'validateOwner' ],
			];
			//			die( var_dump ( $data ) );
			$res = $this->sendRequest ( 'speiTransfer', $data, 'POST', 'JSON', NULL );
			saveLog ( $user, 9, $res[ 'code' ], json_encode ( $data ), json_encode ( $res, JSON_FORCE_OBJECT | JSON_ERROR_NONE ) );
			if ( !$res[ 0 ] ) {
				return [ FALSE, $res[ 'response' ] ];
			}
			if ( !isset( json_decode ( $res[ 'response' ], TRUE )[ 'result' ] ) ) {
				return [ FALSE, json_decode ( $res[ 'response' ], TRUE )[ 'error' ] ];
			}
			return [ TRUE, json_decode ( $res[ 'response' ], TRUE )[ 'result' ] ];
		}
		private function sendRequest ( string $endpoint, array $data, ?string $method, ?string $dataType, ?array $headers ): array {
			$env = getenv ( 'CI_ENVIRONMENT' );
			$url = $env === 'development' ? 'https://magicpay.b4a.io/functions' : 'https://apisandbox.solve.com.mx/public/';
			$method = !empty( $method ) ? strtoupper ( $method ) : 'POST';
			$dataType = !empty( $dataType ) ? strtoupper ( $dataType ) : 'JSON';
			if ( strtoupper ( $dataType ) === 'JSON' ) {
				$headers[] = 'Content-Type: application/json; charset=utf-8';
				$data = json_encode ( $data );
			}
			$headers[] = 'x-parse-application-id:  AMojota1RlBtNDsU2ohnJHSuwA2FZcvybGfNUgyF';
			$headers[] = 'x-parse-rest-api-key:  oRv2uuufUFR1ab0mQdbutMweXfoL3e7eppjTHrs5';
			//			var_dump ( $headers );
			//			die();
			if ( ( $ch = curl_init () ) ) {
				curl_setopt ( $ch, CURLOPT_URL, $url.'/'.$endpoint );
				curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
				curl_setopt ( $ch, CURLOPT_ENCODING, '' );
				curl_setopt ( $ch, CURLOPT_MAXREDIRS, 10 );
				curl_setopt ( $ch, CURLOPT_TIMEOUT, 0 );
				curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
				curl_setopt ( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
				if ( $method == 'POST' ) {
					curl_setopt ( $ch, CURLOPT_POST, TRUE );
				} else {
					curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, $method );
				}
				curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
				curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
				$response = curl_exec ( $ch );
				curl_close ( $ch );
				$code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
				return [ TRUE, 'response' => $response, 'code' => $code ];
			}
			return [
				FALSE,
				'response' => 'No se puede conectar a MagicPay',
				'code'     => curl_getinfo ( $ch, CURLINFO_HTTP_CODE ),
			];
		}
	}