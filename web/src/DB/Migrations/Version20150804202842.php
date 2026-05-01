<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150804202842 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->write('Creating table principals');

        $principals = $schema->createTable('principals');
        $principals->addColumn('path', 'string', ['length' => 255])->setNotnull(true);
        $principals->addColumn('display_name', 'string', ['length' => 255]);
        $principals->addColumn('email', 'string', ['length' => 255]);

        $principals->setPrimaryKey(['path']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('principals');
    }
}
