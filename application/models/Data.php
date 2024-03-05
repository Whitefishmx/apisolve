<?php
	defined ( 'BASEPATH' ) or exit( 'No direct script access allowed' );
	
	class Data extends CI_Model {
		private $link = NULL;
		private $db = NULL;
		private $environment = 'SANDBOX';
		private function connect ( ?string $env = 'SANDBOX' ) {
			if ( $this->link && $this->environment == $env ) {
				return;
			} else if ( $this->link && $this->environment != $env ) {
				mysqli_close ( $this->link );
			}
			$dbhost = $dbuser = $dbpass = $dbtest = $dbprod = '';
			$this->environment = $env;
			include 'Conn.php';
			$this->db = ( $env == 'LIVE' ) ? $dbprod : $dbtest;
			if ( $link = mysqli_connect ( $dbhost, $dbuser, $dbpass ) ) {
				$this->link = $link;
				$this->link->set_charset ( 'utf8mb4' );
			}
		}
	}
