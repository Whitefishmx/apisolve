<?php
	
	namespace App\Controllers\Fintech;
	
	use App\Controllers\BaseController;
	
	class STP extends BaseController {
		public function testCobro (): void {
			$stp = new \App\Models\Stp();
			var_dump ($stp->dispersion ('SANDBOX'));
		}
		
	}