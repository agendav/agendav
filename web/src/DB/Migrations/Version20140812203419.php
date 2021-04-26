<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Schema\Schema;
use \AgenDAV\DB\Migrations\AgenDAVMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140812203419 extends AgenDAVMigration
{
    public function up(Schema $schema)
    {
        $this->skipIf(!$this->upgradingFrom1x(), 'This migration only applies to AgenDAV 1.x upgrades');
        $this->write('Removing old AgenDAV 1.x tables');
        $schema->dropTable('migrations');
        $schema->dropTable('shared');
    }

    public function down(Schema $schema)
    {
        $this->write('Sorry, no way back!');
    }
}
