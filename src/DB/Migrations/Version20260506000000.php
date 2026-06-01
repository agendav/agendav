<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add sess_lifetime column required by Symfony 7 PdoSessionHandler.
 *
 * Symfony 7 added this column to the sessions table. Existing installations
 * upgraded from AgenDAV < 3.0 need to add it manually.
 */
class Version20260506000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('sessions');

        if ($table->hasColumn('sess_lifetime')) {
            $this->write('Column sess_lifetime already exists, skipping');
            return;
        }

        $this->write('Adding sess_lifetime column to sessions table');
        $table->addColumn('sess_lifetime', 'integer', ['unsigned' => true, 'notnull' => true, 'default' => 0]);
        $table->addIndex(['sess_lifetime'], 'sess_lifetime_idx');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('sessions');

        if (!$table->hasColumn('sess_lifetime')) {
            return;
        }

        $table->dropIndex('sess_lifetime_idx');
        $table->dropColumn('sess_lifetime');
    }
}
