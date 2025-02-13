<?php
	
	namespace App\Controllers;
	
	use ExpoSDK\Expo;
	use ExpoSDK\ExpoMessage;
	use ExpoSDK\Exceptions\ExpoException;
	use ExpoSDK\Exceptions\ExpoMessageException;
	use ExpoSDK\Exceptions\InvalidTokensException;
	
	class Notifications extends BaseController {
		public function saveExponentPushToken () {
		
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
			return $data = $response->getData ();
			
		}
	}