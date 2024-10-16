<?php
	
	namespace App\Models;
	class TransactionsModel extends BaseModel {
		public function insertTransaction ( $table, $args, $user ): array {
			$query = "INSERT INTO transactions ( $table, external_id, description, noReference, amount, account_destination, account_origin, status )
VALUES ('{$args['opId']}', '{$args['transactionId']}', '{$args['description']}', '{$args['noReference']}', '{$args['amount']}', '{$args['destination']}',
        '{$args['origin']}', 'process')";
//			var_dump ($query);
//			die();
			$this->db->query ( 'SET NAMES utf8mb4' );
			if ( $this->db->query ( $query ) ) {
				$id = $this->db->insertId ();
				saveLog ( $user, 23, 200, json_encode ( $args ), json_encode ( [ 'id' => $id ], TRUE ) );
				$dataOut = [ 'OperationId' => $args[ 'opId' ], 'TransactionId' => $id ];
				return [ TRUE, $dataOut ];
			} else {
				saveLog ( $user, 23, 400, json_encode ( $args ), json_encode ( [ FALSE, 'No se pudo generar el pedido' ] ) );
				return [ FALSE, 'No se pudo generar el pedido' ];
			}
		}
	}
