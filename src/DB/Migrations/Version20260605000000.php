<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// Stub: leave empty migration to fix #344 while not causing a migration
// conflict on existing installations
class Version20260605000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('SELECT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SELECT 1');
    }
}
