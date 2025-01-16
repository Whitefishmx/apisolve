<?php
	
	namespace App\Controllers;
	
	use Config\Services;
	
	class Email extends PagesStatusCode {
		public function sendPasswordResetEmail ( $toEmail, $code = NULL, $name = NULL ): array {
			$email = Services::email ();
			// Configuración del correo
			$email->setFrom ( 'contacto@solve-express.mx', 'Equipo Solve Express' );
			$email->setTo ( $toEmail );
			$email->setSubject ( 'Restablecer tu contraseña' );
			$data = [ 'name' => $name, 'code' => $code ];
			$email->setMessage ( view ( 'mail/sExpressRecovery', $data ) );
			//			die();
			// Enviar el correo
			if ( $email->send () ) {
				return [ 'status' => 'success', 'message' => 'Correo enviado exitosamente' ];
			} else {
				// Obtener errores en caso de falla
				return [ 'status' => 'error', 'message' => $email->printDebugger ( [ 'headers' ] ) ];
			}
		}
	}