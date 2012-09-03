<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Migration_Ipv6 extends CI_Migration {
    public function up() {
        // Alter ip address field length
        echo "Enlarging ip_address field to support IPv6...\n";
        $this->dbforge->modify_column('sessions',
                array(
                    'ip_address' => array(
                        'TYPE' => 'varchar(45)',
                        'DEFAULT' => '0',
                        ),
                    ));
    }

    public function down() {
        $this->dbforge->modify_column('sessions',
                array(
                    'ip_address' => array(
                        'TYPE' => 'varchar(16)',
                        'DEFAULT' => '0',
                        ),
                    ));
    }
}
