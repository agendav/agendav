<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * This migration fixes several naming mistakes on the principals table
 */
class Version20150809122309 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema->dropTable('principals');

        $principals = $schema->createTable('principals');
        $principals->addColumn('url', 'string', ['length' => 255])->setNotNull(true);
        $principals->addColumn('displayname', 'string', ['length' => 255]);
        $principals->addColumn('email', 'string', ['length' => 255]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
