<?php
	
	namespace App\Controllers;
	
	use Dompdf\Dompdf;
	use App\Models\PDFModel;
	use App\Models\CondusefModel;
	
	class Condusef extends PagesStatusCode {
		public function reuneQueja () {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				$this->logResponse ( 2 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$in = [];
			foreach ( $this->input[ 'quejas' ] as $val ) {
				$in [] = [ "recepcion" => $val[ 'recepcion' ], 'medio' => $val[ 'medio' ], 'cusa' => $val[ 'causa' ] ];
			}
			$condusef = new CondusefModel();
			$queja = $condusef->postReuneGrievance ( $in );
			if ( !$queja ) {
				$this->serverError ( 'Error al enviar los datos', 'Falla en la conexión con CONDUSEF' );
				$this->logResponse ( 2, $this->input );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			//			$queja [ 'Consultas enviadas' ] = [ 'VALUNEx762024wGVSt6', 'VALUNEW762024XDGVSZ6' ];
			$pdfUrls = [];
			foreach ( $queja[ 'Consultas enviadas' ] as $row ) {
				$data = [
					'empresa'  => 'Vatoro, S.A.P.I. de C.V., SOFOM, E.N.R.',
					'dateTime' => date ( 'm/d/Y H:i:s' ),
					'folio'    => $row ];
				$html = view ( 'condusef/reune', $data );
				$pdf = new Dompdf();
				$pdf->loadHtml ( $html );
				$pdf->setPaper ( 'A4', 'portrait' );
				$pdf->render ();
				$filename = 'acuse_queja_'.$row.'.pdf';
				$filePath = FCPATH.'uploads/'.$filename;
				file_put_contents ( $filePath, $pdf->output () );
				$pdfUrls[] = base_url ( '/uploads/'.$filename );
			}
			return $this->getResponse ( $pdfUrls, 200 );
		}
		public function redecoQueja () {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				$this->logResponse ( 2 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
	
			$condusef = new CondusefModel();
			$queja = $condusef->postRedecoGrievance ( $this->input[ 'quejas' ] );
			if ( !$queja ) {
				$this->serverError ( 'Error al enviar los datos', 'Falla en la conexión con CONDUSEF' );
				$this->logResponse ( 2, $this->input );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			//			$queja [ 'Consultas enviadas' ] = [ 'VALUNEx762024wGVSt6', 'VALUNEW762024XDGVSZ6' ];
			$pdfUrls = [];
			foreach ( $queja[ 'Consultas enviadas' ] as $row ) {
				$data = [
					'empresa'  => 'Vatoro, S.A.P.I. de C.V., SOFOM, E.N.R.',
					'dateTime' => date ( 'm/d/Y H:i:s' ),
					'folio'    => $row ];
				$html = view ( 'condusef/reune', $data );
				$pdf = new Dompdf();
				$pdf->loadHtml ( $html );
				$pdf->setPaper ( 'A4', 'portrait' );
				$pdf->render ();
				$filename = 'acuse_queja_'.$row.'.pdf';
				$filePath = FCPATH.'uploads/'.$filename;
				file_put_contents ( $filePath, $pdf->output () );
				$pdfUrls[] = base_url ( '/uploads/'.$filename );
			}
			return $this->getResponse ( $pdfUrls, 200 );
		}
		public function reuneReclamacion () {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				$this->logResponse ( 2 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$in = [];
			//			foreach ( $this->input[ 'reclamaciones' ] as $val ) {
			//				$in [] = [ "recepcion" => $val[ 'recepcion' ], 'medio' => $val[ 'medio' ], 'cusa' => $val[ 'causa' ] ];
			//			}
			$condusef = new CondusefModel();
			$reclamacion = $condusef->postReuneClaims ( $this->input[ 'reclamaciones' ] );
			if ( !$reclamacion ) {
				$this->serverError ( 'Error al enviar los datos', 'Falla en la conexión con CONDUSEF' );
				$this->logResponse ( 2, $this->input );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			//			$queja [ 'Consultas enviadas' ] = [ 'VALUNEx762024wGVSt6', 'VALUNEW762024XDGVSZ6' ];
			$pdfUrls = [];
			foreach ( $reclamacion[ 'Reclamaciones enviadas' ] as $row ) {
				$data = [
					'empresa'  => 'Vatoro, S.A.P.I. de C.V., SOFOM, E.N.R.',
					'dateTime' => date ( 'm/d/Y H:i:s' ),
					'folio'    => $row ];
				$html = view ( 'condusef/reune', $data );
				$pdf = new Dompdf();
				$pdf->loadHtml ( $html );
				$pdf->setPaper ( 'A4', 'portrait' );
				$pdf->render ();
				$filename = 'acuse_queja_'.$row.'.pdf';
				$filePath = FCPATH.'uploads/'.$filename;
				file_put_contents ( $filePath, $pdf->output () );
				$pdfUrls[] = base_url ( '/uploads/'.$filename );
			}
			return $this->getResponse ( $pdfUrls, 200 );
		}
		public function reuneAclaracion () {
			$this->input = $this->getRequestInput ( $this->request );
			if ( $this->verifyRules ( 'POST', $this->request, NULL ) ) {
				$this->logResponse ( 2 );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			$in = [];
			//			foreach ( $this->input[ 'reclamaciones' ] as $val ) {
			//				$in [] = [ "recepcion" => $val[ 'recepcion' ], 'medio' => $val[ 'medio' ], 'cusa' => $val[ 'causa' ] ];
			//			}
			$condusef = new CondusefModel();
			$aclaracion = $condusef->postReuneClarifications ( $this->input[ 'aclaraciones' ] );
			if ( !$aclaracion ) {
				$this->serverError ( 'Error al enviar los datos', 'Falla en la conexión con CONDUSEF' );
				$this->logResponse ( 2, $this->input );
				return $this->getResponse ( $this->responseBody, $this->errCode );
			}
			//			$queja [ 'Consultas enviadas' ] = [ 'VALUNEx762024wGVSt6', 'VALUNEW762024XDGVSZ6' ];
			$pdfUrls = [];
			foreach ( $aclaracion[ 'Aclaraciones enviadas' ] as $row ) {
				$data = [
					'empresa'  => 'Vatoro, S.A.P.I. de C.V., SOFOM, E.N.R.',
					'dateTime' => date ( 'm/d/Y H:i:s' ),
					'folio'    => $row ];
				$html = view ( 'condusef/reune', $data );
				$pdf = new Dompdf();
				$pdf->loadHtml ( $html );
				$pdf->setPaper ( 'A4', 'portrait' );
				$pdf->render ();
				$filename = 'acuse_queja_'.$row.'.pdf';
				$filePath = FCPATH.'uploads/'.$filename;
				file_put_contents ( $filePath, $pdf->output () );
				$pdfUrls[] = base_url ( '/uploads/'.$filename );
			}
			return $this->getResponse ( $pdfUrls, 200 );
		}
	}
