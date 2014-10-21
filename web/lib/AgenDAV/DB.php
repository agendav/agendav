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

namespace AgenDAV;

use \Doctrine\DBAL\Connection;
use \Doctrine\DBAL\DriverManager;

class DB
{
    /**
     * @var Connection
     */
    private $connection;


    /**
     * @param Array $options    Doctrine connection options
     */
    public function __construct($options)
    {
        $dbal_config = new \Doctrine\DBAL\Configuration();
        $this->connection = DriverManager::getConnection($options, $dbal_config);
    }

    /**
     * Returns the DBAL connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
