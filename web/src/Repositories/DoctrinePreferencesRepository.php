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

namespace AgenDAV\Repositories;

use Doctrine\DBAL\Connection;
use AgenDAV\Data\Preferences;


/**
 * Interface for preferences retrieval
 *
 * @author Jorge López Pérez <jorge@adobo.org>
 */
class DoctrinePreferencesRepository implements PreferencesRepository
{

    /**
     * @var Connection
     */
    private $connection;


    /**
     * @param Doctrine\DBAL\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Gets all preferences for the given user
     *
     * @param string $username  User name
     * @return AgenDAV\Data\Preferences
     */
    public function userPreferences($username)
    {
        $sql = 'SELECT options FROM prefs WHERE username = :username';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':username', $username, \PDO::PARAM_STR);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_NUM);

        $data = [];
        if ($rows) {
            $data = json_decode($rows[0][0]);
        }

        return new Preferences($data);
    }

    /**
     * Saves user preferences
     *
     * @param string $username User name
     * @param \AgenDAV\Data\Preferences $preferences
     */
    public function save($username, \AgenDAV\Data\Preferences $preferences)
    {
        $sql = 'UPDATE prefs SET options = :options WHERE username = :username';

        $data = $preferences->to_json();

        $update_stmt = $this->connection->prepare($sql);
        $update_stmt->bindParam(':username', $username, \PDO::PARAM_STR);
        $update_stmt->bindParam(':options', $data, \PDO::PARAM_STR);
        $update_stmt->execute();

        // Preferences for this user were not set
        if (!$update_stmt->rowCount()) {
            $sql_insert = 'INSERT INTO prefs (username, options) VALUES (:username, :options)';
            $insert_stmt = $this->connection->prepare($sql_insert);
            $insert_stmt->bindParam(':username', $username, \PDO::PARAM_STR);
            $insert_stmt->bindParam(':options', $data, \PDO::PARAM_STR);
            try {
                $insert_stmt->execute();
            } catch (\Exception $e) {
                // The user already had his/her preferences stored,
                // and saved them with no changes applied
            }
        }
    }
}
