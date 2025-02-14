<?php namespace App\Models;

use http\Client\Curl\User;

class NotificationModel extends BaseModel {
	public function saveTokenExpo ( $user, $token, $device ): array {
		$query = "INSERT INTO notifications_token ( user_id, token, device ) VALUES '$user', '$token', '$device' ON DUPLICATE KEY UPDATE token = '$token'";
		if ( $this->db->query ( $query ) ) {
			$id = $this->db->insertId ();
			saveLog ( $user, 66, 200, json_encode ( [ $user, $token, $device ] ), json_encode ( [ 'id' => $id ], TRUE ) );
			return [ TRUE, $id ];
		} else {
			saveLog ( $user, 66, 400, json_encode ( [ $user, $token, $device ] ), json_encode ( [ FALSE, 'No se logro guardar el token' ] ) );
			return [ FALSE, 'No se logro guardar el token' ];
		}
	}
	public function insertNotification ( int $user, array $args ): array {
		$query = "INSERT INTO notifications ( user_id, title, subtitle, body, mobile, web ) VALUES ( '$user', '{$args['title']}', '{$args['subtitle']}', '{$args['body']}', '{$args['mobile']}', '{$args['web']}' )";
		if ( $this->db->query ( $query ) ) {
			$id = $this->db->insertId ();
			saveLog ( $user, 66, 200, json_encode ( $args ), json_encode ( [ 'id' => $id ], TRUE ) );
			return [ TRUE, $id ];
		} else {
			saveLog ( $user, 66, 400, json_encode ( $args ), json_encode ( [ FALSE, 'No se logro guardar la notificación' ] ) );
			return [ FALSE, 'No se logro guardar la notificación' ];
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
	public function getNotifications ( $user ): array {
		$query = "SELECT title, subtitle, body, mobile, web, `read`, FORMAT_TIMESTAMP(create_at) FROM notifications WHERE `deleted` = 0 AND user_id = $user ORDER BY `create_at` DESC";
		$res = $this->db->query ( $query );
		if ( $res->getNumRows () > 0 ) {
			$data = $res->getResultArray ();
			foreach ( $data as &$row ) {
				$row [ 'created_at' ] = date ( 'Y-m-d H:i:s', strtotime ( $row [ 'created_at' ] ) );
			}
			return [ TRUE, $data ];
		}
		return [ FALSE, 'No se encontraron notificaciones' ];
	}
}