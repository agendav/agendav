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

use Doctrine\DBAL\Schema\Schema;
use AgenDAV\DB\Migrations\AgenDAVMigration;

/**
 * Converts the shares.options column from the legacy Doctrine 'array' type
 * (PHP serialize()) to JSON. The DBAL 'array' type was removed in DBAL 4, and
 * the Share entity now maps $options with type 'json' (matching Preferences).
 *
 * The underlying column stays 'text'; only the stored representation changes,
 * so no schema ALTER is needed. Rows that are already JSON (e.g. fresh installs
 * or a re-run) are left untouched, making this migration idempotent.
 */
class Version20260524120000 extends AgenDAVMigration
{
    public function up(Schema $schema): void
    {
        $this->write('Converting shares.options from PHP-serialized to JSON');

        foreach ($this->connection->fetchAllAssociative('SELECT sid, options FROM shares') as $row) {
            $value = (string) $row['options'];
            $decoded = @unserialize($value, ['allowed_classes' => false]);

            // Not PHP-serialized (already JSON, empty, or unexpected) -> leave as-is.
            // 'b:0;' is the legitimate serialization of boolean false.
            if ($decoded === false && $value !== 'b:0;') {
                continue;
            }

            $this->connection->update(
                'shares',
                ['options' => json_encode($decoded === false ? [] : $decoded)],
                ['sid' => $row['sid']]
            );
        }

        // Avoid the "migration was executed but did not result in any SQL" notice.
        $this->addSql('SELECT 1');
    }

    public function down(Schema $schema): void
    {
        $this->write('Reverting shares.options from JSON to PHP-serialized');

        foreach ($this->connection->fetchAllAssociative('SELECT sid, options FROM shares') as $row) {
            $decoded = json_decode((string) $row['options'], true);

            // Not valid JSON (already serialized, or null) -> leave as-is.
            if ($decoded === null) {
                continue;
            }

            $this->connection->update(
                'shares',
                ['options' => serialize($decoded)],
                ['sid' => $row['sid']]
            );
        }

        $this->addSql('SELECT 1');
    }
}
