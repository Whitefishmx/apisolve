<?php
	defined ( 'BASEPATH' ) or exit( 'No direct script access allowed' );
	$this->environment = 'SANDBOX'; //Ambiente de trabajo
	$this->dbsandbox = 'apisandbox_sandbox'; //Base de datos de Pruebas
//	private string $dbprod = 'compensapay';
	$this->dbprod = 'apisandbox_sandbox'; //Base de datos de Producción
	$this->APISandbox = 'apisandbox_sandbox';
	$this->APILive = 'apisolve_live';