<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20260605000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sess_lifetime column to sessions table (required by Symfony PdoSessionHandler)';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->createSchemaManager()->introspectTableByUnquotedName('sessions')->getColumns();
        if (isset($columns['sess_lifetime'])) {
            $this->write('sess_lifetime column already exists, skipping');
            return;
        }

        $this->write('Adding sess_lifetime column to sessions table');

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform) {
            $this->addSql('ALTER TABLE sessions ADD COLUMN sess_lifetime INTEGER UNSIGNED NOT NULL DEFAULT 0');
        } else {
            $this->addSql('ALTER TABLE sessions ADD COLUMN sess_lifetime INTEGER NOT NULL DEFAULT 0');
        }
    }

    public function down(Schema $schema): void
    {
        $columns = $this->connection->createSchemaManager()->introspectTableByUnquotedName('sessions')->getColumns();
        if (!isset($columns['sess_lifetime'])) {
            return;
        }

        $this->addSql('ALTER TABLE sessions DROP COLUMN sess_lifetime');
    }
}
