<?php
	
	namespace App\Controllers;
	class Home extends BaseController {
		public function index (): string {
			return view ( 'documenter' );
		}
		public function acuseReune (): string {
//			$data = [ 'empresa'  => 'Vatoro, S.A.P.I. de C.V., SOFOM, E.N.R.',
//			          'dateTime' => date ( 'm/d/Y H:i:s' ),
//			          'folio'    => 'VALUNE/762024/DGVS/6' ];
			return view ( 'condusef/reune', $data );
		}
		public function acuseRedeco (): string {
			return view ( 'condusef/redeco' );
		}
	}
