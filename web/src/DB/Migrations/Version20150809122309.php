<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * This migration fixes several naming mistakes on the principals table
 */
class Version20150809122309 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->dropTable('principals');
    }

    public function down(Schema $schema): void
    {
    }
}
