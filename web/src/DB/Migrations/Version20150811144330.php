<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150811144330 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema->dropTable('shares');
        $shares_table = $schema->createTable('shares');
        $sid = $shares_table->addColumn('sid', 'integer', ['unsigned' => true]);
        $sid->setAutoIncrement(true);
        $shares_table->addColumn('owner', 'string', ['length' => 255]);
        $shares_table->addColumn('calendar', 'string', ['length' => 255]);
        $shares_table->addColumn('with', 'string', ['length' => 255]);
        $shares_table->addColumn('options', 'text');
        $shares_table->addColumn('rw', 'boolean');

        $shares_table->setPrimaryKey(['sid']);
        $shares_table->addIndex(['owner', 'calendar']);
        $shares_table->addIndex(['with']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
