<?php
	
	namespace App\Models;
	use CodeIgniter\Database\Exceptions\DataException;
	use Exception;
	
	class OperationModel extends BaseModel {
		/**
		 * Busca que tipo de operación es la que se va a realizar según el número de referencia ingresado
		 *
		 * @param string|NULL $description Descripción de la transferencia
		 * @param string|NULL $refNumeric  Referencia numerica
		 * @param string|NULL $trackingKey Clave de rastreo
		 * @param string|NULL $env         Ambiente en el que se va a trabajar
		 *
		 * @return array Datos preliminares y la identificación de que operación es
		 */
		public function searchOperations ( string $description = NULL, string $refNumeric = NULL, string $trackingKey = NULL, string $env = NULL ): mixed {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$wh1 = $description != NULL ? " operation_number = '$description' OR " : '';
			$wh2 = $description != NULL ? " reference_number = '$description' OR " : '';
			$wh3 = $description != NULL ? " reference_number = '$description' OR " : '';
			$wh1 .= $refNumeric != NULL ? " operation_number = '$refNumeric' OR " : '';
			$wh2 .= $refNumeric != NULL ? " reference_number = '$refNumeric' OR " : '';
			$wh3 .= $refNumeric != NULL ? " reference_number = '$refNumeric' OR " : '';
			$wh1 .= $trackingKey != NULL ? " operation_number = '$trackingKey' " : '';
			$wh2 .= $trackingKey != NULL ? " reference_number = '$trackingKey' " : '';
			$wh3 .= $trackingKey != NULL ? " reference_number = '$trackingKey' " : '';
			$query = "SELECT id, operation_number AS 'opNumber', folio_operation AS 'folio', id, 'conciliacion' AS 'origin'
FROM $this->base.operations
WHERE STATUS = 1 AND ($wh1)
UNION SELECT id, reference_number AS 'opNumber', folio AS 'folio', id, 'conciliacionPlus' AS 'origin'
      FROM $this->base.conciliation_plus
      WHERE status = 1 AND ($wh2)
      UNION SELECT id, reference_number AS 'opNumber', folio AS 'folio', id,'dispercionPlus' AS 'origin'
            FROM $this->base.dispersions_plus
            WHERE status = 1 AND ($wh3)";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de ' . $res ];
			}
			$res = $res->getResultArray ();
			return count ( $res ) > 0 ? $res[ 0 ] : $res;
//			for ( $i = 0; count ( $res ) > 0; $i++ ) {
//				switch ( $res[ $i ][ 'origin' ] ) {
//					case 'conciliacion':
//						$query = "SELECT t1.id, t1.operation_number, t1.id_client, t2.legal_name as 'cname', t2.rfc as 'crfc', t2.account_clabe as 'cclabe',
//					t1.id_provider, t3.legal_name as 'pname', t3.rfc as 'prfc', t3.account_clabe as 'pclabe',
//					t4.arteria_clabe, (t5.total*100) AS 'entry_money', (t6.total*100) AS 'exit_money_d', (t8.total*100) as 'exit_money_f',
//					t3.account_clabe as 'companyClabe', t3.legal_name, t7.bnk_clave, t5.uuid,
//					t9.name AS 'provName', t9.last_name AS 'provLast', t9.email AS 'provEmail', t2.legal_name AS 'provCompany',
//					t10.name AS 'clientName', t10.last_name AS 'clientLast', t10.email AS 'clientEmail', t3.legal_name AS 'clientCompany'
//					FROM $this->base.operations t1
//					    LEFT JOIN $this->base.companies t2 ON t1.id_client = t2.id
//					    LEFT JOIN $this->base.companies t3 ON t1.id_provider = t3.id
//					    INNER JOIN $this->base.fintech t4 ON t4.companie_id = t1.id_provider
//					    INNER JOIN $this->base.invoices t5 ON t1.id_invoice = t5.id
//					    LEFT JOIN $this->base.debit_notes t6 ON t1.id_debit_note = t6.id
//					    INNER JOIN $this->base.cat_bancos t7 ON t2.id_broadcast_bank = t7.bnk_id
//					    LEFT JOIN $this->base.invoices t8 ON t1.id_invoice_relational = t8.id
//					    INNER JOIN $this->base.users t9 ON t9.id_company = t3.id
//					    INNER JOIN $this->base.users t10 ON t10.id_company = t2.id
//					WHERE t1.status = 1 AND t1.operation_number = '{$res[$i]['opNumber']}' AND t1.folio = '{$res[$i]['folio']}'";
//						if ( $subRes = $this->db->query ( $query ) ) {
//							$subRes = $subRes->getResultArray ();
//							if ( !empty( $subRes ) ) {
//								$res[$i]['details'] = $subRes;
//							}
//						}
//						break;
//					case 'conciliacionCPlus':
//						$query = "SELECT t1.id, t2.legal_name AS 'legal_client', t2.short_name AS 'short_client',
//       t3.legal_name AS 'legal_provider', t3.short_name AS 'short_provider', t1.reference_number, t1.folio, t1.folio_dispersion, t1.entry_money, t1.exit_money,
//       DATE_FORMAT(FROM_UNIXTIME(t1.payment_date), '%Y-%m-%d') AS 'payment_date', t1.`status`,
//       DATE_FORMAT(FROM_UNIXTIME(t1.created_at), '%Y-%m-%d %H:%i:%s') AS 'created_at',
//       t4.arteria_clabe AS 'provider_fintech', t5.arteria_clabe AS 'client_fintech', t9.email AS 'provider_mail', t10.email AS 'client_mail'
//FROM apisandbox_sandbox.conciliation_plus t1
//    LEFT JOIN apisandbox_sandbox.companies t2 ON t1.id_client = t2.id
//    LEFT JOIN apisandbox_sandbox.companies t3 ON t1.id_provider = t3.id
//    INNER JOIN apisandbox_sandbox.fintech t4 ON t4.companie_id = t1.id_provider
//    INNER JOIN apisandbox_sandbox.fintech t5 ON t5.companie_id = t1.id_client
//    INNER JOIN apisandbox_sandbox.users t9 ON t9.id_company = t3.id
//    INNER JOIN apisandbox_sandbox.users t10 ON t10.id_company = t2.id
//    WHERE t1.folio = '{$res[$i]['folio']}' AND t1.status = 1";
//						if ( $subRes = $this->db->query ( $query ) ) {
//							$subRes = $subRes->getResultArray ();
//							if ( !empty( $subRes ) ) {
//								$res[$i]['details'] = $subRes;
//							}
//						}
//						break;
//					case 'dispercionPlus':
//						$query = "";
//						if ( $subRes = $this->db->query ( $query ) ) {
//							$subRes = $subRes->getResultArray ();
//							if ( !empty( $subRes ) ) {
//								$res[$i]['details'] = $subRes;
//							}
//						}
//						break;
//				}
//			}
		}
		public function AddMovement ( array $args, string $env = NULL ): string {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			//Se obtiene la información faltante para insertar los datos
			$sourceBank = $this->getBankByClabe ( substr ( $args[ 'sourceBank' ], 0, 3 ) );
			$receiverBank = $this->getBankByClabe ( substr ( $args[ 'receiverBank' ], 0, 3 ) );
			$fecha = strtotime ( $args[ 'transactionDate' ] );
			//Se genera el query para insertar datos
			$query = "INSERT INTO $this->base.balance (operationNumber, traking_key, arteriaD_id, amount, descriptor,
                                 source_bank, receiver_bank, source_rfc, receiver_rfc,
                                 source_clabe, receiver_clabe, transaction_date) VALUES ( ";
			$query .= $args[ 'operationNumber' ] === NULL ? "NULL, " : "'{$args['operationNumber']}', ";
			$query .= $args[ 'trakingKey' ] === NULL ? "NULL, " : "'{$args['trakingKey']}', ";
			$query .= "'{$args['opId']}', '{$args['amount']}', '{$args['descriptor']}',
					'{$sourceBank['bnk_code']}', '{$receiverBank['bnk_code']}', ";
			$query .= $args[ 'sourceRfc' ] === NULL ? "NULL, " : "'{$args['sourceRfc']}', ";
			$query .= $args[ 'receiverRfc' ] === NULL ? "NULL, " : "'{$args['receiverRfc']}', ";
			$query .= "'{$args['sourceBank']}', '{$args['receiverBank']}', '$fecha') ";
			//Se verífica que se pueda insertar la información
			try {
				$res = $this->db->query($query);
			}catch (Exception $e) {
				return 'hola';
			}
			
			if (!$res) {
				throw new Exception('error in query');
				return false;
			}
			//En caso correcto
			return [ "code" => 200, "result" => $this->db->insertID () ];
		}
		public function getBankByClabe ( string $clabe, string $env = NULL ) {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "SELECT * FROM $this->base.cat_bancos WHERE bnk_clave = '{$clabe}'";
			if ( $result = $this->db->query ( $query ) ) {
				$result = $result->getResultArray ();
				return count ( $result ) > 0 ? $result[ 0 ] : $result;
			}
		}
	}