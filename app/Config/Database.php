<?php
	
	namespace Config;
	
	use CodeIgniter\Database\Config;
	
	/**
	 * Database Configuration
	 */
	class Database extends Config {
		/**
		 * The directory that holds the Migrations
		 * and Seeds directories.
		 */
		public string $filesPath = APPPATH.'Database'.DIRECTORY_SEPARATOR;
		/**
		 * Lets you choose which connection group to
		 * use if no other is specified.
		 */
		public string $defaultGroup = 'default';
		/**
		 * The default database connection.
		 */
		public array $default = [
			'DSN'      => '',
			'hostname' => '',
			'username' => '',
			'password' => '',
			'database' => '',
			'DBDriver' => 'MySQLi',
			'DBPrefix' => '',
			'pConnect' => FALSE,
			'DBDebug'  => TRUE,
			'charset'  => 'utf8',
			'DBCollat' => 'utf8mb4_unicode_ci',
			'swapPre'  => '',
			'encrypt'  => FALSE,
			'compress' => FALSE,
			'strictOn' => FALSE,
			'failover' => [],
			'port'     => 3306,
		];
		public array $sandbox = [
			'DSN'      => '',
			'hostname' => '',
			'username' => '',
			'password' => '',
			'database' => '',
			'DBDriver' => 'MySQLi',
			'DBPrefix' => '',
			'pConnect' => FALSE,
			'DBDebug'  => TRUE,
			'charset'  => 'utf8',
			'DBCollat' => 'utf8mb4_unicode_ci',
			'swapPre'  => '',
			'encrypt'  => FALSE,
			'compress' => FALSE,
			'strictOn' => FALSE,
			'failover' => [],
			'port'     => 3306,
		];
		/**
		 * This database connection is used when
		 * running PHPUnit database tests.
		 */
		public array $tests = [
			'DSN'         => '',
			'hostname'    => 'localhost',
			'username'    => '',
			'password'    => '',
			'database'    => ':memory:',
			'DBDriver'    => 'SQLite3',
			'DBPrefix'    => '',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
			'pConnect'    => FALSE,
			'DBDebug'     => TRUE,
			'charset'     => 'utf8',
			'DBCollat'    => 'utf8_general_ci',
			'swapPre'     => '',
			'encrypt'     => FALSE,
			'compress'    => FALSE,
			'strictOn'    => FALSE,
			'failover'    => [],
			'port'        => 3306,
			'foreignKeys' => TRUE,
			'busyTimeout' => 1000,
		];
		public function __construct () {
			parent::__construct ();
			// Ensure that we always set the database group to 'tests' if
			// we are currently running an automated test suite, so that
			// we don't overwrite live data on accident.
			if ( ENVIRONMENT === 'testing' ) {
				$this->defaultGroup = 'tests';
			} else if ( ENVIRONMENT === 'development' ) {
				$this->defaultGroup = 'sandbox';
			} else if ( ENVIRONMENT === 'production' ) {
				$this->defaultGroup = 'default';
			} else {
				$this->defaultGroup = 'sandbox';
			}
		}
	}
