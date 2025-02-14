<?php
	
	namespace App\Controllers;
	
	use ExpoSDK\Expo;
	use ExpoSDK\ExpoMessage;
	use App\Models\NotificationModel;
	use ExpoSDK\Exceptions\ExpoException;
	use CodeIgniter\HTTP\ResponseInterface;
	use ExpoSDK\Exceptions\ExpoMessageException;
	use ExpoSDK\Exceptions\InvalidTokensException;
	
	class Notifications extends PagesStatusCode {
		protected string|NotificationModel $notification = '';
		public function __construct () {
			parent::__construct ();
			$this->notification = new NotificationModel();
		}
		public function saveExponentPushToken (): ResponseInterface|array {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$res = $this->notification->saveTokenExpo ( $this->user, $this->input[ 'token' ], $this->input[ 'device' ] );
			if ( !$res[ 0 ] ) {
				$this->serverError ( 'Error al guardar el token', 'No se pudo guardar el token' );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Token guardado correctamente',
			];
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function insertNotification (){
			$this->input = $this->getRequestInput ( $this->request );
            if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
                return $this->getResponse ( $this->responseBody, $this->errCode );
            }
            $res = $this->notification->insertNotification ( $this->user, $this->input );
            if ( !$res[ 0 ] ) {
                $this->serverError ( 'Error al insertar la notificación', 'No se pudo insertar la notificación' );
                return $this->getResponse ( $this->responseBody, $this->errCode );
            }
            $this->responseBody = [
                'error'       => $this->errCode = 200,
                'description' => 'Notificación insertada correctamente',
            ];
		}
		
		/**
		 * @throws ExpoException
		 * @throws InvalidTokensException
		 * @throws ExpoMessageException
		 */
		public function sendNotification (): array {
			$message = ( new ExpoMessage() )
				->setTitle ( 'This title overrides initial title' )
				->setSubtitle ()
				->setBody ( 'This notification body overrides initial body' )
				->setData ( [ 'id' => 1 ] )
				->setChannelId ( 'IMPORTANCE_HIGH' )
				->setBadge ( 0 )
				->playSound ()
				->setPriority ( 'high' )
				->setMutableContent ( TRUE );
			$defaultRecipients = [
				'ExponentPushToken[YPxRxFPp2jmC4g0FdsgI_-]',
				'ExponentPushToken[HJgVpMLz41M1r3UeBhUQ8V]' ];
			$response = ( new Expo )->send ( $message )->to ( $defaultRecipients )->push ();
			return $response->getData ();
			
		}
	}