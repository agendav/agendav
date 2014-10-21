<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Schema\Schema;
use \AgenDAV\DB\Migrations\AgenDAVMigration;

class Version20140812113548 extends AgenDAVMigration
{
    public function up(Schema $schema)
    {
        $this->skipIf(!$this->upgradingFrom1x(), 'This migration only applies to AgenDAV 1.x upgrades');
        $this->write('Migrating from AgenDAV 1.x tables');
        $schema->dropTable('sessions');
    }

    public function down(Schema $schema)
    {
        $this->write('Sorry, no way back!');
    }
}
