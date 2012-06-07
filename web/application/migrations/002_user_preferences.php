<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Migration_User_preferences extends CI_Migration {
	public function up() {
		echo "Creating table field...\n";

		$option_fields = array(
				'fieldid' => array(
					'type' => 'INT',
					'constraint' => 9,
					'unsigned' => TRUE,
					'auto_increment' => TRUE,
					),
				'name' => array(
					'type' => 'VARCHAR',
					'constraint' => '64',
					'null' => FALSE,
					),
				'description' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => FALSE,
					),
				);
		$this->dbforge->add_field($option_fields);
		// Primary key
		$this->dbforge->add_key('fieldid', TRUE);

		// Additional key
		$this->dbforge->add_key('name');

		$this->dbforge->create_table('field');

		// Make name column unique
		$this->db->query('ALTER TABLE field ADD CONSTRAINT '
				.'unique_name UNIQUE (name)');

		// Set engine to InnoDB on MySQL
		if (preg_match('/^mysql/', $this->db->dbdriver)) {
			$this->db->query('ALTER TABLE field '
					.'ENGINE=InnoDB');
		}

		// User preferences table
		echo "Creating table userpref...\n";

		// DBForge doesn't support foreign keys
		if (preg_match('/^mysql/', $this->db->dbdriver)) {
			$sql = <<< 'MYSQL'
				CREATE TABLE userpref (
						username VARCHAR(255) NOT NULL,
						fieldid INT UNSIGNED NOT NULL,
						value TEXT NOT NULL,
						PRIMARY KEY(username, fieldid),
						FOREIGN KEY (fieldid) REFERENCES field(fieldid)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
MYSQL;
		} elseif ($this->db->dbdriver == 'postgre') {
			$sql = <<< 'PGSQL'
				CREATE TABLE userpref (
						username varchar(255) not null,
						fieldid integer not null references
							field(fieldid),
						value text not null,
						primary key (username, fieldid));
PGSQL;
		} else {
			echo 'Unsupported database driver!';
			die();
		}

		$this->db->query($sql);

		// Create hidden_calendars preference
		$hidden = array(
				'name' => 'hidden_calendars',
				'description' => 
					'List of calendars set to be hidden',
				);
		echo "Creating field hidden_calendars...\n";
		$this->db->insert('field', $hidden);

	}

	public function down() {
		$this->dbforge->drop_table('userpref');
		$this->dbforge->drop_table('field');
	}
}
