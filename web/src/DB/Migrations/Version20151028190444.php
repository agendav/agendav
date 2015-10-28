<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151028190444 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
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
        // this down() migration is auto-generated, please modify it to your needs

    }
}
