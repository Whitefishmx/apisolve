<?php namespace App\Models;

class NotificationModel extends BaseModel {
	public function insertNotification ( int $user, array $args ): array {
		$query = "INSERT INTO notifications ( user_id, title, subtitle, body, mobile, web ) VALUES ( '{$args['user']}', '{$args['title']}', '{$args['subtitle']}', '{$args['body']}', '{$args['mobile']}', '{$args['web']}' )";
		if ( $this->db->query ( $query ) ) {
			$id = $this->db->insertId ();
			saveLog ( $user, 66, 200, json_encode ( $args ), json_encode ( [ 'id' => $id ], TRUE ) );
			return [ TRUE, $id ];
		} else {
			saveLog ( $user, 66, 400, json_encode ( $args ), json_encode ( [ FALSE, 'No se logro guardar la notificación' ] ) );
			return [ FALSE, 'No se logro guardar la notificación' ];
		}
	}
	public function saveTokenExpo ( $user, $token, $device ): array {
		$query = "INSERT INTO notifications_token ( user_id, token, device ) VALUES ('$user', '$token', '$device') ON DUPLICATE KEY UPDATE token = '$token'";
//		var_dump ($query);die();
		if ( $this->db->query ( $query ) ) {
			$id = $this->db->insertId ();
			saveLog ( $user, 66, 200, json_encode ( [ $user, $token, $device ] ), json_encode ( [ 'id' => $id ], TRUE ) );
			return [ TRUE, $id ];
		} else {
			saveLog ( $user, 66, 400, json_encode ( [ $user, $token, $device ] ), json_encode ( [ FALSE, 'No se logro guardar el token' ] ) );
			return [ FALSE, 'No se logro guardar el token' ];
		}
	}
	public function markAsDeleted ( $user, int $id ): array {
		$query = "UPDATE notifications SET `read` = 1, deleted = 1 WHERE `id` = '$id'";
		if ( $this->db->query ( $query ) ) {
			$id = $this->db->insertId ();
			saveLog ( $user, 66, 200, json_encode ( [ 'read' => $id ] ), json_encode ( [ 'id' => $id ], TRUE ) );
			return [ TRUE, $id ];
		} else {
			saveLog ( $user, 66, 400, json_encode ( [ 'read' => $id ] ), json_encode ( [ FALSE, 'No se logro actualizar el estatus de la notificación' ] ) );
			return [ FALSE, 'No se logro actualizar el estatus de la notificación' ];
		}
	}
	public function markAsRead ( $user, int $id ): array {
		$query = "UPDATE notifications SET `read` = 1 WHERE `id` = '$id'";
		if ( $this->db->query ( $query ) ) {
			$id = $this->db->insertId ();
			saveLog ( $user, 66, 200, json_encode ( [ 'read' => $id ] ), json_encode ( [ 'id' => $id ], TRUE ) );
			return [ TRUE, $id ];
		} else {
			saveLog ( $user, 66, 400, json_encode ( [ 'read' => $id ] ), json_encode ( [ FALSE, 'No se logro actualizar el estatus de la notificación' ] ) );
			return [ FALSE, 'No se logro actualizar el estatus de la notificación' ];
		}
	}
	/**
	 * @param $user
	 *
	 * @return array|null
	 */
	public function getNotifications ( $user ): array|null {
		$query = "SELECT id ,title, subtitle, body, mobile, web, `read`, FORMAT_TIMESTAMP(create_at) as `date` FROM notifications WHERE `deleted` = 0 AND user_id = $user ORDER BY `create_at` DESC";
		if ( !$res = $this->db->query ( $query ) ) {
			$this->resultsNotFound ( 500, 'Error al obtener notificaciones', 'No se lograron obtener notificaciones' );
		}
		if ( $res->getNumRows () < 1 ) {
			$this->resultsNotFound ( 404, 'Error al obtener notificaciones', 'No se encontraron notificaciones' );
		}
		return [ TRUE, $res->getResultArray () ];
	}
	public function getExpoToken ( $user ): array {
		$query = "SELECT * FROM notifications_token WHERE user_id = $user";
		$res = $this->db->query ( $query );
		if ( $res->getNumRows () > 0 ) {
			return [ TRUE, $res->getResultArray () ];
		}
		return [ FALSE, 'No se encontraron notificaciones' ];
	}
}