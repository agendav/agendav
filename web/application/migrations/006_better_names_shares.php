<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Migration_Better_names_shares extends CI_Migration {
    public function up() {
        echo "Renaming some columns on shares table...\n";

        $new_shares_table = $this->db->dbprefix('shares');
        if (preg_match('/^mysql/', $this->db->dbdriver)) {
            $sqls = array(
                "ALTER TABLE $new_shares_table CHANGE user_from grantor VARCHAR(255) NOT NULL",
                "ALTER TABLE $new_shares_table CHANGE calendar path VARCHAR(255) NOT NULL",
                "ALTER TABLE $new_shares_table CHANGE user_which grantee VARCHAR(255) NOT NULL",
                "ALTER TABLE $new_shares_table CHANGE write_access rw tinyint(1) NOT NULL DEFAULT '0'",
            );
        } elseif ($this->db->dbdriver == 'postgre') {
            $sqls = array(
               "ALTER TABLE $new_shares_table RENAME COLUMN user_from TO grantor",
               "ALTER TABLE $new_shares_table RENAME COLUMN calendar TO path",
               "ALTER TABLE $new_shares_table RENAME COLUMN user_which TO grantee",
               "ALTER TABLE $new_shares_table RENAME COLUMN write_access TO rw",
            );
        } else {
            echo 'Unsupported database driver!';
            die();
        }

        foreach ($sqls as $sql) {
            $res = $this->db->simple_query($sql);
            if ($res === false) {
                echo "SQL error!\n";
                echo $this->db->_error_message();
                exit;
            }
        }
    }

    public function down() {
    }
}
