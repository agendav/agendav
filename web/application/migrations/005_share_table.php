<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Migration_Share_table extends CI_Migration {
    public function up() {
        echo "Renaming table shared...\n";

        $shared_table = $this->db->dbprefix('shared');
        $new_shares_table = $this->db->dbprefix('shares');
        if (preg_match('/^mysql/', $this->db->dbdriver)) {
            $sql = <<<MYSQL
                RENAME TABLE $shared_table TO $new_shares_table;
MYSQL;
        } elseif ($this->db->dbdriver == 'postgre') {
            $sql = <<<PGSQL
                ALTER TABLE $shared_table RENAME TO $new_shares_table;
PGSQL;
        } else {
            echo 'Unsupported database driver!';
            die();
        }

        $this->db->simple_query($sql);
    }

    public function down() {
    }
}
