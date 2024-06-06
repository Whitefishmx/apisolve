<?php
	
	namespace App\Models;
	class CfdiModel extends BaseModel {
		/**
		 * Función para poder obtener los grupos de conciliaciones posibles
		 *
		 * @param array       $data Arreglo de documentos para generar conciliaciones
		 * @param string|NULL $env  Entorno en el que se va a trabajar
		 *
		 * @return array $res Arreglo con las conciliaciones que se pueden crear
		 */
		public function createTmpInvoices ( array $data, string $env = NULL ): array {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$tmpName = md5 ( rand () );
			$query = "CREATE TABLE $this->base.invoices_$tmpName (
    sender_rfc VARCHAR(20) NOT NULL COLLATE 'utf8mb4_spanish_ci',
	receiver_rfc VARCHAR(20) NOT NULL COLLATE 'utf8mb4_spanish_ci',
	uuid VARCHAR(36) NOT NULL COLLATE 'utf8mb4_spanish_ci',
	tipo VARCHAR(1) NOT NULL COLLATE 'utf8mb4_spanish_ci',
	invoice_date VARCHAR(50) NOT NULL DEFAULT '' COLLATE 'utf8mb4_spanish_ci',
	total DECIMAL(10,2) NOT NULL,
	xml_document TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_spanish_ci',
	UNIQUE INDEX uuid (uuid) USING BTREE) COLLATE = 'utf8mb4_spanish_ci' ENGINE = InnoDB";
			if ( $this->db->query ( $query ) ) {
				foreach ( $data as $val ) {
					$inDate = strtotime ( $val[ 'fecha' ] );
					$query = "INSERT INTO $this->base.invoices_$tmpName values ('{$val['emisor']['rfc']}', '{$val['receptor']['rfc']}', '{$val['uuid']}', '{$val['tipo']}','$inDate', '{$val['monto']}', '{$val['xml']}')";
					if ( !$this->db->query ( $query ) ) {
						return [ FALSE, 'No se pudieron insertar los registros.' ];
					}
				}
				$query = "SELECT t1.sender_rfc AS sender, t1.receiver_rfc AS receiver, t1.sender_name, t1.receiver_name, w.W AS 'in', s.S AS 'out',
       (IF(w.W > s.S, w.W - s.S, s.S - w.W)) AS difference,
       (IF(w.W > s.S, 'Favor', 'Contra')) AS saldo, (s.uuid_s + w.uuid_w) AS total_cfdi
FROM (SELECT rf.sender_rfc, rf.receiver_rfc, cm.short_name AS 'sender_name', cm2.short_name AS 'receiver_name'
      FROM $this->base.invoices_$tmpName rf
          INNER JOIN $this->base.companies cm ON cm.rfc = rf.sender_rfc
          INNER JOIN $this->base.companies cm2 ON cm2.rfc = rf.receiver_rfc
      GROUP BY sender_rfc, receiver_rfc) AS t1
    LEFT JOIN (SELECT COUNT(UUID) AS 'uuid_w', sender_rfc, receiver_rfc, SUM(total) AS W
               FROM $this->base.invoices_$tmpName
               GROUP BY sender_rfc, receiver_rfc) AS w
        ON t1.sender_rfc = w.sender_rfc AND t1.receiver_rfc = w.receiver_rfc
    LEFT JOIN (SELECT COUNT(UUID) AS 'uuid_s', sender_rfc, receiver_rfc, SUM(total) AS S
               FROM $this->base.invoices_$tmpName
               GROUP BY sender_rfc, receiver_rfc) AS s
        ON t1.sender_rfc = s.receiver_rfc AND t1.receiver_rfc = s.sender_rfc";
				if ( $res = $this->db->query ( $query ) ) {
					$item = [];
					$res = $res->getResultArray ();
					$rfcCompanies = $this->getCompaniesRegisters ( $env );
					if ( !$rfcCompanies[ 0 ] ) {
						return [ FALSE, 'No se lograron obtener resultados' ];
					}
					for ( $i = 0; $i < count ( $res ); $i++ ) {
						if ( count ( $item ) > 0 ) {
							$counter = 0;
							for ( $j = 0; $j < count ( $item ); $j++ ) {
								if ( ( $res[ $i ][ 'sender' ] === $item[ $j ][ 'sender' ] || $res[ $i ][ 'sender' ] === $item[ $j ][ 'receiver' ] ) &&
									( $res[ $i ][ 'receiver' ] === $item[ $j ][ 'sender' ] || $res[ $i ][ 'receiver' ] === $item[ $j ][ 'receiver' ] ) ) {
									$counter++;
								}
							}
							if ( $counter === 0 ) {
								$res[ $i ][ 'tmp' ] = "invoices_$tmpName";
								$item [] = $res[ $i ];
							}
						} else {
							$res[ $i ][ 'tmp' ] = "invoices_$tmpName";
							$item [] = $res[ $i ];
						}
					}
					$finalItem = [];
					$finalItemErr = [];
					for ( $i = 0; $i < count ( $item ); $i++ ) {
						$counter = 0;
						for ( $j = 0; $j < count ( $rfcCompanies ); $j++ ) {
							if ( $item[ $i ][ 'sender' ] === $rfcCompanies[ $j ][ 'rfc' ] || $item[ $i ][ 'receiver' ] === $rfcCompanies[ $j ][ 'rfc' ] ) {
								$counter++;
							}
						}
						if ( $counter >= 2 ) {
							$finalItem[] = $item[ $i ];
						} else {
							$finalItemErr[] = $item[ $i ];
						}
					}
					$conciliaciones  [ 'conciliaciones' ] = $finalItem;
					if ( !empty( $finalItemErr ) ) {
						$conciliaciones  [ 'errors' ] = $finalItemErr;
					}
					//					$query = "DROP TABLE $this->base.invoices_$tmpName";
					//					if ( !$this->db->query ( $query ) ) {
					//						$conciliaciones[ 'errors' ] = 'tabla temporal persiste';
					//					}
					return $conciliaciones;
				}
				return [ FALSE, 'No se lograron formar los grupos de conciliaciones' ];
			}
			return [ FALSE, 'No se logro iniciar el proceso para generar una conciliación masiva.' ];
		}
		/**
		 * @param string|NULL $env
		 *
		 * @return array
		 */
		public function getCompaniesRegisters ( string $env = NULL ): array {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "SELECT rfc FROM $this->base.companies WHERE active = 1";
			if ( $res = $this->db->query ( $query ) ) {
				return $res->getResultArray ();
			}
			return [ FALSE, 'No se lograron obtener resultados' ];
		}
		/**
		 * Función para guardar los CFDI que si serán usados para conciliaciones masivas
		 *
		 * @param array       $data RFC de las empresas e información de balance de las conciliaciones
		 * @param string|NULL $env  Ambiente en el que trabajara la BD LIVE|SANDBOX
		 *
		 * @return array Arreglo con los datos de las conciliaciones y los ID de los CFDI que se utilizaran
		 */
		public function savePermanentCfdi ( array $data, string $env = NULL ): array {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$counter = 0;
			foreach ( $data as $row ) {
				$query = "INSERT INTO $this->base.cfdi_plus (sender_rfc, receiver_rfc, uuid, tipo, invoice_date, total, xml_document)
    (SELECT * FROM $row[2] WHERE (sender_rfc = '$row[0]' AND receiver_rfc = '$row[1]') OR (sender_rfc = '$row[1]' AND receiver_rfc = '$row[0]'))";
				if ( $this->db->query ( $query ) ) {
					$data[ $counter ][ 'insertedId' ] = $this->db->insertID ();
					$data[ $counter ][ 'affected' ] = $this->db->affectedRows ();
				}
				$counter++;
			}
			if ( count ( $data ) > 0 ) {
				$counter--;
				$query = "DROP TABLE $this->base.{$data[$counter][2]}";
				if ( $this->db->query ( $query ) ) {
					return $data;
				}
				return [ FALSE, '2.2 No se logro guardar la información requerida para las conciliaciones.' ];
			}
			return [ FALSE, '2.1 No se logro guardar la información requerida para las conciliaciones.' ];
		}
		public function getCfdiPdf ( array $args, string $env = 'SANDBOX' ) {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = '';
			switch ( $args[ 'category' ] ) {
				case '1':
					$query = "SELECT uuid, xml_document, total, sender_rfc, receiver_rfc FROM $this->base.invoices WHERE UUID = '{$args['uuid']}'";
					break;
				case '2':
					$query = "SELECT uuid, xml_document, total, sender_rfc, receiver_rfc FROM $this->base.cfdi_plus WHERE UUID = '{$args['uuid']}'";
					break;
				case '3':
					$query = "SELECT uuid, xml_document, total, sender_rfc, receiver_rfc FROM $this->base.debit_notes WHERE UUID = '{$args['uuid']}'";
					break;
			}
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de conciliaciones' ];
			}
			$res = $res->getResultArray ();
			if ( empty( $res ) ) {
				return $res;
			}
			return $res[ 0 ];
		}
		public function getDocsCfdi ( $from, $to, $fromUpdate, $toUpdate, $uuid, $args, $show, $company, $env = NULL ): array {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$url = 'https://compensapay.local/assets/factura';
			$query1 = "SELECT * FROM ( (SELECT CONCAT(t1.id,'f') AS id2, t2.id, t3.short_name AS 'emisor', t4.short_name AS 'receptor', t2.uuid,
                        CONCAT( '$url' , '/factura.php?idfactura=', t2.id ) AS 'idurl',
                        DATE_FORMAT(FROM_UNIXTIME(t2.invoice_date), '%d-%m-%Y') AS 'dateCFDI',
                        DATE_FORMAT(FROM_UNIXTIME(t2.created_at), '%d-%m-%Y') AS 'dateCreate',
                        DATE_FORMAT(FROM_UNIXTIME(t1.payment_date), '%d-%m-%Y') AS 'dateToPay',
                        t2.total, 'Factura' AS tipo, t1.created_at
                 FROM $this->base.operations t1
                     INNER JOIN $this->base.invoices t2 ON t1.id_invoice = t2.id OR t1.id_invoice_relational = t2.id
                     LEFT JOIN $this->base.companies t3 ON t2.sender_rfc = t3.rfc
                     LEFT JOIN $this->base.companies t4 ON t2.receiver_rfc = t4.rfc
                 WHERE (t1.id_client = $company OR t1.id_provider = $company)
                   AND t2.status = 3
                   AND t2.created_at >= '$from' AND t2.created_at <= '$to'
                   AND t2.invoice_date >= '$fromUpdate' AND t2.invoice_date <= '$toUpdate'
                   AND t2.`uuid` LIKE '%$uuid%'
                   AND (t2.sender_rfc LIKE '%$args%' OR t2.receiver_rfc LIKE '%$args%' OR t3.legal_name LIKE '%$args%'  OR t4.legal_name LIKE '%$args%'
                            OR t3.short_name LIKE '%$args%' OR t4.short_name LIKE '%$args%' OR t1.operation_number LIKE '%$args%' OR t1.folio_operation LIKE '%$args%') )
UNION (SELECT CONCAT(t1.id,'n') AS id2, t2.id, t3.short_name AS 'emisor', t4.short_name AS 'receptor', t2.uuid,
              CONCAT('$url' , '/nota.php?idnota=', t2.id ) AS 'idurl',
              DATE_FORMAT(FROM_UNIXTIME(t2.debitNote_date), '%d-%m-%Y') AS 'dateCFDI',
              DATE_FORMAT(FROM_UNIXTIME(t2.created_at), '%d-%m-%Y') AS 'dateCreate',
              DATE_FORMAT(FROM_UNIXTIME(t1.payment_date), '%d-%m-%Y') AS 'dateToPay',
              t2.total, 'Nota de crédito' AS tipo, t1.created_at
       FROM $this->base.operations t1
           INNER JOIN $this->base.debit_notes t2 ON t1.id_debit_note = t2.id
           LEFT JOIN $this->base.companies t3 ON t2.sender_rfc = t3.rfc
           LEFT JOIN $this->base.companies t4 ON t2.receiver_rfc = t4.rfc
       WHERE (t3.id = 1 OR t4.id = 1)
         AND t2.status = 3
         AND t2.created_at >= '$from' AND t2.created_at <= '$to'
         AND t2.debitNote_date >= '$fromUpdate' AND t2.debitNote_date <= '$toUpdate'
         AND t2.uuid LIKE '%$uuid%'
         AND (t2.sender_rfc LIKE '%$args%' OR t2.receiver_rfc LIKE '%$args%' OR t3.legal_name LIKE '%$args%'  OR t4.legal_name LIKE '%$args%'
                  OR t3.short_name LIKE '%$args%' OR t4.short_name LIKE '%$args%' OR t1.operation_number LIKE '%$args%' OR t1.folio_operation LIKE '%$args%')) ) AS T
         ORDER BY T.created_at";
			$queryW = "SELECT invoice_range FROM $this->base.conciliation_plus WHERE id_client = $company OR id_provider = $company";
			$query2 = "SELECT CONCAT(t1.id,'fp') AS id2, t1.id, t3.short_name AS 'emisor', t3.short_name AS 'receptor', t1.uuid,
       CONCAT( '$url' , '/factura.php?idfactura=', t2.id ) AS 'idurl',
       DATE_FORMAT(FROM_UNIXTIME(t1.invoice_date), '%d-%m-%Y') AS 'dateCFDI',
       DATE_FORMAT(FROM_UNIXTIME(t1.created_at), '%d-%m-%Y') AS 'dateCreate',
       DATE_FORMAT(FROM_UNIXTIME(t1.updated_at), '%d-%m-%Y') AS 'dateToPay',
       t1.total, 'CFDI Masivo' AS tipo, t1.created_at
FROM apisandbox_sandbox.cfdi_plus t1
    LEFT JOIN apisandbox_sandbox.companies t2 ON t1.sender_rfc = t2.rfc
    LEFT JOIN apisandbox_sandbox.companies t3 ON t1.receiver_rfc = t3.rfc
WHERE (";
			$query2W = ") AND t1.created_at >= '$from' AND t1.created_at <= '$to'
			AND t1.invoice_date >= '$fromUpdate' AND t1.invoice_date <= '$toUpdate'
			AND t1.`uuid` LIKE '%$uuid%'
			AND (t2.rfc LIKE '%$args%' OR t2.rfc LIKE '%$args%' OR t2.legal_name LIKE '%$args%'  OR t3.legal_name LIKE '%$args%' OR t2.short_name LIKE '%$args%'
			OR t3.short_name LIKE '%$args%')";
			$where = '';
			switch ( $show ) {
				case 1:
					if ( !$res = $this->db->query ( $query1 ) ) {
						return [ FALSE, 'No se encontró información de conciliaciones' ];
					}
					$items = $res->getResultArray ();
					break;
				case 2:
					if ( !$res = $this->db->query ( $queryW ) ) {
						return [ FALSE, 'No se encontró información de conciliaciones' ];
					}
					foreach ( $res->getResultArray () as $value ) {
						$id = explode ( '-', $value[ 'invoice_range' ] );
						$where .= "t1.id BETWEEN  $id[0] AND $id[1] OR ";
					}
					$where = substr ( $where, 0, -3 );
					$resQuery = $query2 . $where . $query2W;
					if ( !$res2 = $this->db->query ( $resQuery ) ) {
						return [ FALSE, 'No se encontró información de conciliaciones' ];
					}
					$items = $res2->getResultArray ();
					break;
				default:
					if ( !$res = $this->db->query ( $query1 ) ) {
						return [ FALSE, 'No se encontró información de conciliaciones' ];
					}
					$items = $res->getResultArray ();
					if ( !$res = $this->db->query ( $queryW ) ) {
						return [ FALSE, 'No se encontró información de conciliaciones' ];
					}
					foreach ( $res->getResultArray () as $value ) {
						$id = explode ( '-', $value[ 'invoice_range' ] );
						$where .= "t1.id BETWEEN  $id[0] AND $id[1] OR ";
					}
					$where = substr ( $where, 0, -3 );
					$resQuery = $query2 . $where . $query2W;
					if ( !$res = $this->db->query ( $resQuery ) ) {
						return [ FALSE, 'No se encontró información de conciliaciones' ];
					}
					$items = array_merge ( $items, $res->getResultArray () );
			}
			return ( $items );
		}
	}