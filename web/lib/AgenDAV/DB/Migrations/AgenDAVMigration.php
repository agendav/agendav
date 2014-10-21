<?php

/*
 * Copyright 2014 Jorge López Pérez <jorge@adobo.org>
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

use Doctrine\DBAL\Migrations\AbstractMigration;

abstract class AgenDAVMigration extends AbstractMigration
{
    /**
     * Checks if there is a table named 'migrations', which suggests we were
     * using AgenDAV 1.x
     *
     * @return bool
     */
    protected function upgradingFrom1x()
    {
        $tables = $this->connection->getSchemaManager()->listTables();

        foreach ($tables as $table) {
            if ($table->getName() == 'migrations') {
                return true;
            }
        }

        return false;
    }
}
