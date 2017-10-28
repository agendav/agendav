<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Schema\Schema;
use \AgenDAV\DB\Migrations\AgenDAVMigration;

class Version20140812133707 extends AgenDAVMigration
{
    public function up(Schema $schema)
    {
        $this->write('Creating initial schema for AgenDAV');
        $this->createSessionsTable($schema);
        $this->createSharesTable($schema);

        if (!$this->upgradingFrom1x()) {
            $prefs = $schema->createTable('prefs');
            $prefs->addColumn('username', 'string', ['length' => 255]);
            $prefs->addColumn('options', 'text');
            $prefs->setPrimaryKey(['username']);
        }
    }

    public function down(Schema $schema)
    {
        $this->write('Sorry, no way back!');
    }

    protected function createSharesTable(Schema $schema)
    {
        $shares_table = $schema->createTable('shares');
        $sid = $shares_table->addColumn('sid', 'integer', ['unsigned' => true]);
        $sid->setAutoincrement(true);
        $shares_table->addColumn('owner', 'string', ['length' => 255]);
        $shares_table->addColumn('calendar', 'string', ['length' => 255]);
        $shares_table->addColumn('with', 'string', ['length' => 255]);
        $shares_table->addColumn('options', 'text');
        $shares_table->addColumn('rw', 'boolean');

        $shares_table->setPrimaryKey(['sid']);
        $shares_table->addIndex(['owner', 'calendar']);
        $shares_table->addIndex(['with']);
    }

    public function createSessionsTable(Schema $schema)
    {
        $sessions = $schema->createTable('sessions');
        $sessions->addColumn('sess_id', 'string');
        $sessions->addColumn('sess_data', 'text')->setNotnull(true);
        $sessions->addColumn('sess_time', 'integer')->setNotnull(true)->setUnsigned(true);
        $sessions->setPrimaryKey(array('sess_id'));
    }
    
}
