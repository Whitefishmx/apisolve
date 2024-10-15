<?php
	
	namespace App\Database\Migrations;
	
	use CodeIgniter\Database\Migration;
	
	class AddUser extends Migration {
		public function up () {
			$this->forge->addField ( [
				'id' => [
					'type' => 'INT',
					'constraint' => 5,
					'unsigned' => TRUE,
					'auto_increment' => TRUE,
				],
				'name' => [
					'type' => 'VARCHAR',
					'constraint' => '100',
					'null' => FALSE,
				],
				'email' => [
					'type' => 'VARCHAR',
					'constraint' => '100',
					'null' => FALSE,
					'unique' => TRUE,
				],
				'password' => [
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => FALSE,
					'unique' => TRUE,
				],
				'updated_at' => [
					'type' => 'datetime',
					'null' => TRUE,
				],
				'created_at datetime default current_timestamp',
			] );
			$this->forge->addPrimaryKey ( 'id' );
			$this->forge->createTable ( 'user' );
		}
		public function down () {
			$this->forge->dropTable ( 'user' );
		}
	}