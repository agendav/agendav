<?php

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160407194955 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->write('Creating new sessions table');
        $session_handler = new PdoSessionHandler($this->connection->getWrappedConnection());
        $session_handler->createTable();
        // Trick to avoid the 'was executed but did not result in any SQL statements' message
        $this->addSql('SELECT 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->throwIrreversibleMigrationException();
    }
}
