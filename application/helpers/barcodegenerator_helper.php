<?php
//require 'vendor/autoload.php';
use Picqer\Barcode;


	function generateBarcode($code){

	// This will output the barcode as HTML output to display in the browser
	$generator = new Barcode\BarcodeGeneratorPNG();
    $img = '';
    try {
        $img ='<img width="150px" height="40px" src="data:image/png;base64,' . base64_encode($generator->getBarcode($code, $generator::TYPE_CODE_39)) . '">';
    }catch (Exeption $e){
        $img = "{'Error' : '07'}";
    }
	return $img;
	}

?>