<?php
	
	namespace app\Models;
	
	use Dompdf\Dompdf;
	use App\Models\BaseModel;
	
	require 'vendor';
	
	class PDFModel extends BaseModel {
		public function getAcuseReune ( $html, $filename ): void {
			$dom = new Dompdf();
			$dom->loadHtml ( $html );
			$dom->setPaper ( 'letter', 'portrait' );
			$dom->render ();
			$dom->stream("reporte_usuario.pdf", array("Attachment" => 1));
		}
	}