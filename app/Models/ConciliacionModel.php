<?php
	
	namespace App\Models;
	class ConciliacionModel extends BaseModel {
		public string $urlSolve = "https://compensapay.local/";
		/**
		 * Crear conciliaciones a partir de los grupos generados en la carga de CFDI
		 *
		 * @param array       $args arreglo con los datos necesarios para generar
		 * @param string|NULL $env  Ambientes  EN QUE SE V
		 *
		 * @return array Resultados de las conciliaciones
		 */
		public function makeConciliationPlus ( array $args, string $env = NULL ): array {
			$company = json_decode ( base64_decode ( array_pop ( $args ) ), TRUE );
			$user = json_decode ( base64_decode ( array_pop ( $args ) ), TRUE );
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$counter = 0;
			$autoApproved = [];
			foreach ( $args as $row ) {
				helper ( 'tools_helper' );
				helper ( 'tetraoctal_helper' );
				$companies = $this->getClientProviderByRfc ( $row[ 0 ], $row[ 1 ], $env );
				$range = $row[ 'insertedId' ] + ( intval ( $row[ 'affected' ] ) - 1 );
				$nexID = $this->getNexId ( 'conciliation_plus' );
				$opNumber = MakeOperationNumber ( $nexID );
				$data = [ 2, $nexID, $user[ 'id' ], $companies[ 'client' ][ 'id' ], $companies[ 'provider' ][ 'id' ], strtotime ( 'now' ) ];
				$folio = serialize32 ( $data );
				$paymentDate = strtotime ( '+40 days' );
				$status = $company[ 'rfc' ] === $companies[ 'client' ][ 'rfc' ] ? 1 : 0;
				$query = "INSERT INTO $this->base.conciliation_plus (invoice_range, id_client, id_provider, reference_number, folio, entry_money, exit_money, payment_date, status)
VALUES ('{$row['insertedId']}-$range', '{$companies['client']['id']}', '{$companies['provider']['id']}', '$opNumber', '$folio', '$row[3]', '$row[4]', '$paymentDate', $status)";
				if ( !$this->db->query ( $query ) ) {
					return [ FALSE, 'No se encontró información de conciliaciones' ];
				}
				if ( $company[ 'rfc' ] === $companies[ 'client' ][ 'rfc' ] ) {
					$args[ $counter ][ 'idConciliation' ] = $this->db->insertID ();
					$autoApproved[] = $args[ $counter ];
				}
				$counter++;
			}
			return $autoApproved;
		}
		/**
		 * Obtiene la información de las empresas que estarán implicadas en las conciliaciones
		 *
		 * @param string      $client   RFC del "cliente"
		 * @param string      $provider RFC del "proveedor"
		 * @param string|NULL $env      Ambiente en el que se trabajara SANDBOX|LIVE
		 *
		 * @return array Arreglo con la información de ambas empresas
		 */
		public function getClientProviderByRfc ( string $client, string $provider, string $env = NULL ): array {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$companies = [];
			$query = "SELECT * FROM $this->base.companies WHERE rfc = '$client'";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de ' . $client ];
			}
			$companies[ 'client' ] = $res->getResultArray ()[ 0 ];
			$query = "SELECT * FROM $this->base.companies WHERE rfc = '$provider'";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de ' . $provider ];
			}
			$companies[ 'provider' ] = $res->getResultArray ()[ 0 ];
			return $companies;
		}
		/**
		 * Obtener todas las conciliaciones sencillas
		 * @param string      $from Fecha desde donde comenzar a buscar
		 * @param string      $to Fecha hasta donde buscar
		 * @param mixed       $id Id de la empresa que se quiere obtener las conciliaciones
		 * @param string|NULL $env Ambiente en el que se va a trabajar
		 *
		 * @return void
		 */
		public function getConciliations ( string $from, string $to, mixed $id, string $env = NULL ) {
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$query = "";
		}
		/**
		 * Obtiene las Conciliaciones Plus de una empresa
		 *
		 * @param mixed       $id  Id de la empresa a buscar conciliaciones
		 * @param string|NULL $env ambiente en el que se trabajará
		 *
		 * @return array|array[] Arreglo con la información necesaria para mostrar las conciliaciones
		 */
		public function getConciliationsPlus ( string $from, string $to, mixed $id, string $env = NULL ): array {
			//Se declara el ambiente a utilizar
			$this->environment = $env === NULL ? $this->environment : $env;
			$this->base = strtoupper ( $this->environment ) === 'SANDBOX' ? $this->APISandbox : $this->APILive;
			$url = $this->urlSolve . 'assets/factura/factura.php?idfactura=';
			$query = "SELECT t1.id, t1.status, t3.fintech_clabe AS 'clabeTransferencia', t1.reference_number, t1.folio, t1.invoice_range, t1.entry_money, t1.exit_money,
       DATE_FORMAT(FROM_UNIXTIME(t1.payment_date), '%d-%m-%Y') AS 'payment_date', t4.short_name AS 'deudor', t5.short_name AS 'receptor', t4.rfc
FROM $this->base.conciliation_plus t1
    INNER JOIN $this->base.fintech_clabes t2 ON t1.id_client = t2.companie_id
    INNER JOIN $this->base.fintech_clabes t3 ON t1.id_provider = t3.companie_id
    INNER JOIN $this->base.companies t4 ON t1.id_client = t4.id
    INNER JOIN $this->base.companies t5 ON t1.id_provider = t5.id
WHERE ( t1.id_client = $id OR t1.id_provider = $id ) AND ( t1.created_at BETWEEN '$from' AND '$to' )";
			if ( !$res = $this->db->query ( $query ) ) {
				return [ FALSE, 'No se encontró información de conciliaciones' ];
			}
			$res = $res->getResultArray ();
			if ( empty( $res ) ) {
				return $res;
			}
			for ( $i = 0; $i < count ( $res ); $i++ ) {
				$range = explode ( '-', $res[ $i ][ 'invoice_range' ] );
				$query = "SELECT t1.uuid, t2.short_name as 'receiber', t3.short_name as 'sender', t1.total,  CONCAT('$url', t1.id) AS 'idurl',
       DATE_FORMAT(FROM_UNIXTIME(t1.created_at), '%d-%m-%Y') AS 'created_at',
       DATE_FORMAT(FROM_UNIXTIME(t1.invoice_date), '%d-%m-%Y') AS 'payment_date',
       (IF(t1.tipo = 'I', 'CFDI', 'Nota de credito') ) AS 'tipo'
FROM apisandbox_sandbox.cfdi_plus t1
    INNER JOIN apisandbox_sandbox.companies t2 ON t1.receiver_rfc = t2.rfc
    INNER JOIN apisandbox_sandbox.companies t3 ON t1.sender_rfc = t3.rfc
WHERE t1.id BETWEEN $range[0] AND $range[1]";
				if ( !$cfdi = $this->db->query ( $query ) ) {
					return [ FALSE, 'No se encontró información de los CFDI' ];
				}
				$res[ $i ][ 'cfdi' ] = $cfdi->getResultArray ();
			}
			return $res;
		}
	}
