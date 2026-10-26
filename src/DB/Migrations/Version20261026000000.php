<?php

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AgenDAV\DB\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * Widens sessions.sess_lifetime to INT UNSIGNED on MySQL/MariaDB.
 *
 * AgenDAV 2.x did not create this column, so it was often added by hand
 * (e.g. MEDIUMINT, as in old Symfony docs). Version20260506000000 keeps an
 * existing column as is, but Symfony 7 stores an expiry timestamp there,
 * which overflows narrower types (SQLSTATE 22003).
 *
 * The native type is read from information_schema because DBAL maps
 * MEDIUMINT and INT to the same Doctrine type.
 */
class Version20261026000000 extends AgenDAVMigration
{
    private const SAFE_TYPES = ['int unsigned', 'bigint', 'bigint unsigned'];

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('sessions');

        if (!$table->hasColumn('sess_lifetime')) {
            $this->write('Column sess_lifetime does not exist, skipping');
            return;
        }

        if ($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            $type = $this->connection->fetchOne(
                "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sessions' AND COLUMN_NAME = 'sess_lifetime'"
            );
            // Strip display widths, e.g. "int(10) unsigned" -> "int unsigned"
            $type = strtolower(preg_replace('/\(\d+\)/', '', (string) $type));

            if (!in_array($type, self::SAFE_TYPES, true)) {
                $this->write(sprintf('Widening sessions.sess_lifetime from %s to INT UNSIGNED', $type));
                $this->addSql('UPDATE sessions SET sess_lifetime = 0 WHERE sess_lifetime IS NULL OR sess_lifetime < 0');
                $this->addSql('ALTER TABLE sessions MODIFY sess_lifetime INT UNSIGNED NOT NULL DEFAULT 0');
            }
        }

        if (!$this->hasIndexOn($table, 'sess_lifetime')) {
            $this->write('Adding index on sessions.sess_lifetime');
            $table->addIndex(['sess_lifetime'], $table->hasIndex('sess_lifetime_idx') ? null : 'sess_lifetime_idx');
        }
    }

    public function down(Schema $schema): void
    {
        // Narrowing the column again would reintroduce the overflow
    }

    private function hasIndexOn(Table $table, string $column): bool
    {
        foreach ($table->getIndexes() as $index) {
            $columns = $index->getIndexedColumns();
            if (count($columns) === 1
                && strcasecmp($columns[0]->getColumnName()->getIdentifier()->getValue(), $column) === 0) {
                return true;
            }
        }

        return false;
    }
}
