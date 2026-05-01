<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160407193445 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->write('Dropping old sessions table');
        $schema->dropTable('sessions');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
