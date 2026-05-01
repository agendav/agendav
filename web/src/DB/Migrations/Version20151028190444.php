<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151028190444 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $principals = $schema->createTable('principals');
        $principals->addColumn('url', 'string', ['length' => 255])->setNotnull(true);
        $principals->addColumn('displayname', 'string', ['length' => 255]);
        $principals->addColumn('email', 'string', ['length' => 255]);

        $principals->setPrimaryKey(['url']);
    }

    public function down(Schema $schema): void
    {
    }
}
