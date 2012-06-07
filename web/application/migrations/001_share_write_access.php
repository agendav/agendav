<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Migration_Share_write_access extends CI_Migration {
	public function up() {
		echo "Adding column write_access to shared table...\n";
		
		// DBForge PostgreSQL support is broken
		// (https://github.com/EllisLab/CodeIgniter/issues/808)
		// Can't use it to add a new column :-(
		$this->db->query("ALTER TABLE shared ADD COLUMN write_access BOOLEAN
				NOT NULL DEFAULT '0'");

		// AgenDAV only provided write access prior to version 1.2.5
		echo "Setting initial value for write_access...\n";
		$q = $this->db->update('shared', array('write_access' => '1'));
	}

	public function down() {
		$this->dbforge->drop_column('shared',
				'write_access');
	}
}
