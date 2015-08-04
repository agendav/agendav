<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150804202842 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->write('Creating table principals');

        $principals = $schema->createTable('principals');
        $principals->addColumn('path', 'string', ['length' => 255])->setNotNull(true);
        $principals->addColumn('display_name', 'string', ['length' => 255]);
        $principals->addColumn('email', 'string', ['length' => 255]);

        $principals->setPrimaryKey(['path']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('principals');
    }
}
