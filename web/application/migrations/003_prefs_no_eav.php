<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Migration_Prefs_no_eav extends CI_Migration {
	public function up() {
		echo "Dropping table field and userpref...\n";

		$this->dbforge->drop_table('userpref');
		$this->dbforge->drop_table('field');

		// New user preferences table
		echo "Creating table prefs...\n";

		// DBForge doesn't support foreign keys
		if (preg_match('/^mysql/', $this->db->dbdriver)) {
			$sql = <<< 'MYSQL'
				CREATE TABLE prefs (
						username VARCHAR(255) NOT NULL,
						options TEXT NOT NULL,
						PRIMARY KEY(username)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
MYSQL;
		} elseif ($this->db->dbdriver == 'postgre') {
			$sql = <<< 'PGSQL'
				CREATE TABLE prefs (
						username varchar(255) not null,
						options text not null,
						primary key (username));
PGSQL;
		} else {
			echo 'Unsupported database driver!';
			die();
		}

		$this->db->query($sql);

	}

	public function down() {
		// Create field and userpref tables again? No way

		$this->dbforge->drop_table('prefs');
	}
}
