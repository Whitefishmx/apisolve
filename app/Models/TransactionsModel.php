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
		public function updateTransactionStatus ( $folio, $noRef, $status, $user ): array {
			$query = "UPDATE transactions SET status = '$status' WHERE external_id like '%$folio%' AND noReference = '$noRef'";
			if ( $this->db->query ( $query ) ) {
				$affected = $this->db->affectedRows ();
				if ( $affected > 0 ) {
					saveLog ( $user, 26, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
					( [ 'affected' => $affected ] ) );
					return [ TRUE, 'Se actualizó el estado de las transacciones' ];
				}
				saveLog ( $user, 26, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
				( [ FALSE, 'affected' => $affected ] ) );
				return [ FALSE, 'No se encontró registro a actualizar' ];
			}
			saveLog ( $user, 26, 200, json_encode ( [ 'folio' => $folio, 'noReference' => $noRef, 'status' => $status ] ), json_encode
			( [ FALSE, 'affected' => $this->db->error () ] ) );
			return [ FALSE, 'No se pudo actualizar el estado de las transacciones' ];
		}
	}
