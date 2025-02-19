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
		protected NotificationModel $notification;
		public function __construct () {
			parent::__construct ();
			$this->notification = new NotificationModel;
		}
		public function saveExponentPushToken (): ResponseInterface {
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
				'response'    => 'ok',
			];
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function getNotification (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$res = $this->notification->getNotifications ( $this->user );
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Notificaciones obtenidos',
				'response'    => $res[ 1 ],
			];
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		/**
		 * @throws ExpoMessageException
		 * @throws ExpoException
		 * @throws InvalidTokensException
		 */
		public function insertNotification (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$counter = 0;
			$total = count ( $this->input );
			$errors = [];
			foreach ( $this->input as $value ) {
				$res = $this->notification->insertNotification ( $this->user, $value );
				if ( !$res[ 0 ] ) {
					$errors[] = $value;
				}
				if ( $value[ 'mobile' ] === 1 ) {
					$this->sendNotification ( $value );
				}
				$counter++;
			}
			if ( $total !== $counter ) {
				$this->serverError ( 'Error al insertar la notificación', json_encode ( $errors ) );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Notificaciónes insertadas correctamente',
				'response'    => 'ok',
			];
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		/**
		 * @throws ExpoException
		 * @throws InvalidTokensException
		 * @throws ExpoMessageException
		 */
		public function sendNotification ( $args ): array {
			$message = ( new ExpoMessage() )
				->setTitle ( $args[ 'title' ] )
				->setSubtitle ( $args[ 'subtitle' ] )
				->setBody ( $args[ 'body' ] )
				->setData ( [ 'id' => 1 ] )
				->setChannelId ( 'IMPORTANCE_HIGH' )
				->setBadge ( 0 )
				->playSound ()
				->setPriority ( 'high' )
				->setMutableContent ( TRUE );
			$defaultRecipients = [];
			$res = $this->notification->getExpoToken ( $args[ 'user' ] );
			foreach ( $res[ 1 ] as $value ) {
				$defaultRecipients[] = "ExponentPushToken[{$value['token']}]";
			}
			$response = ( new Expo )->send ( $message )->to ( $defaultRecipients )->push ();
			return $response->getData ();
		}
		public function deleteNotification (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			if ( count ( $this->input ) > 1 ) {
				$counter = 0;
				foreach ( $this->input as $value ) {
					$res = $this->notification->markAsDeleted ( $this->user, $value[ 'id' ] );
					if ( !$res[ 0 ] ) {
						$counter++;
					}
				}
				if ( $counter > 0 ) {
					$this->serverError ( 'Error al cambiar eñ estatus de las notificaciónes', "No se pudo eliminar las notificaciónes intente nuevamente" );
				}else{
					$this->responseBody = [
						'error'       => $this->errCode = 200,
						'description' => 'Notificaciónes eliminadas correctamente',
						'response'    => 'ok',
					];
				}
				return $this->getResponse ( $this->responseBody, $this->errCode );
			} else {
				$res = $this->notification->markAsDeleted ( $this->user, $this->input[ 'id' ] );
				if ( !$res[ 0 ] ) {
					$this->serverError ( 'Error al cambiar eñ estatus de la notificación', 'No se pudo eliminar la notificación intente nuevamente' );
					return $this->getResponse ( $this->responseBody, $this->errCode );
				}
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Notificación eliminada correctamente',
				'response'    => 'ok',
			];
			return $this->getResponse ( $this->responseBody, $this->errCode );
			
			
			
//			$res = $this->notification->markAsDeleted ( $this->user, $this->input[ 'id' ] );
//
//			if ( !$res[ 0 ] ) {
//				$this->serverError ( 'Error al cambiar eñ estatus de la notificación', 'No se pudo eliminar la notificación intente nuevamente' );
//				return $this->getResponse ( $this->responseBody, $this->errCode );
//			}
//			$this->responseBody = [
//				'error'       => $this->errCode = 200,
//				'description' => 'Notificación eliminada correctamente',
//				'response'    => 'ok',
//			];
//			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
		public function readNotification (): ResponseInterface {
			$this->input = $this->getRequestInput ( $this->request );
//			var_dump ($this->input);die();
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			if ( count ( $this->input ) > 1 ) {
				$counter = 0;
				foreach ( $this->input as $value ) {
					$res = $this->notification->markAsRead ( $this->user, $value[ 'id' ] );
					if ( !$res[ 0 ] ) {
						$counter++;
					}
				}
				if ( $counter > 0 ) {
					$this->serverError ( 'Error al cambiar eñ estatus de las notificaciónes', "No se pudieron actualizar $counter notificaciones" );
				}else{
					$this->responseBody = [
						'error'       => $this->errCode = 200,
						'description' => 'Estatus de las notificaciónes se cambiaron correctamente',
						'response'    => 'ok',
					];
				}
				return $this->getResponse ( $this->responseBody, $this->errCode );
			} else {
				$res = $this->notification->markAsRead ( $this->user, $this->input[ 'id' ] );
				if ( !$res[ 0 ] ) {
					$this->serverError ( 'Error al cambiar eñ estatus de la notificación', 'No se pudo marcar como leida intente nuevamente' );
					return $this->getResponse ( $this->responseBody, $this->errCode );
				}
			}
			$this->responseBody = [
				'error'       => $this->errCode = 200,
				'description' => 'Estatus de la notificación cambiado correctamente',
				'response'    => 'ok',
			];
			return $this->getResponse ( $this->responseBody, $this->errCode );
		}
	}