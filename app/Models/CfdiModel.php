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
				$query = "SELECT t1.sender_rfc AS sender, t1.receiver_rfc AS receiver, w.W AS 'in', s.S AS 'out',
       (IF(w.W > s.S, w.W - s.S, s.S - w.W)) AS difference,
       (IF(w.W > s.S, 'Favor', 'Contra')) AS saldo
FROM (SELECT sender_rfc, receiver_rfc
      FROM $this->base.invoices_$tmpName
      GROUP BY sender_rfc, receiver_rfc) AS t1
    LEFT JOIN (SELECT sender_rfc, receiver_rfc, SUM(total) AS W
               FROM $this->base.invoices_$tmpName
               GROUP BY sender_rfc, receiver_rfc) AS w
        ON t1.sender_rfc = w.sender_rfc AND t1.receiver_rfc = w.receiver_rfc
    LEFT JOIN (SELECT sender_rfc, receiver_rfc, SUM(total) AS S
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
//			$ids = [];
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
	}
