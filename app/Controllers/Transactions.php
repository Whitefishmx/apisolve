<?php
	
	namespace App\Controllers;
	
	use App\Models\TransactionsModel;
	
	class Transactions extends PagesStatusCode {
		public function downloadCep (): string {
			$transaction = new TransactionsModel();
			$data = $transaction->getDataForCep ();
			$total = count ($data[1]);
			echo "Se encontraron: $total CEP para descargar...".PHP_EOL;
			if ( !$data[ 0 ] ) {
				return "Proceso finalizado".PHP_EOL;
			}
			$res = [];
			$counter = 0;
			foreach ( $data[ 1 ] as $value ) {
				$counter ++;
				echo "descargando $counter de $total: {$value['noReference']} ".PHP_EOL;
				$download = $transaction->DownloadCEP ( $value, 0 );
				if ( $download > 0 ) {
					$folio = str_replace ( "SSOLVE", "", $value[ 'external_id' ] );
					$res[] = [ 'idTransaction' => $value[ 'id' ], 'folio' => $folio, 'filename' => $download ];
				}
			}
			$files = glob ( "Resource id #*" );
			foreach ( $files as $file ) {
				if ( is_file ( $file ) ) { // Verificar que sea un archivo
					unlink ( $file );      // Eliminar el archivo
				}
			}
			foreach ( $res as $key ) {
				$transaction->insertCep ( $key );
			}
			return "Proceso finalizado".PHP_EOL;
		}
	}
