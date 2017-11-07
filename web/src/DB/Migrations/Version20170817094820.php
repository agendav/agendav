<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170817094820 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $subscriptions_table = $schema->createTable('subscriptions');
        $sid = $subscriptions_table->addColumn('sid', 'integer', ['unsigned' => true]);
        $sid->setAutoIncrement(true);
        $subscriptions_table->addColumn('owner', 'string', ['length' => 255]);
        $subscriptions_table->addColumn('calendar', 'string', ['length' => 255]);
        $subscriptions_table->addColumn('options', 'text');

        $subscriptions_table->setPrimaryKey(['sid']);
        $subscriptions_table->addIndex(['owner', 'calendar']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
