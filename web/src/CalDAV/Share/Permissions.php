<?php

namespace AgenDAV\CalDAV\Share;

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

/**
 * Stores lists of permissions by profile (owner, read-write, read-only and default)
 */
class Permissions
{
    /**
     * Lists of permissions per role
     *
     * @var Array
     */
    private $list;

    /**
     * Creates a new Permissions manager
     *
     * @param array $permissions Associative array, where keys are role names
     *                           (owner, read-write, read-only and default) and
     *                           values are arrays of permissions in Clark
     *                           notation
     */
    public function __construct(Array $permissions = [])
    {
        $this->list = $permissions;
    }

    /**
     * Sets permissions for a given role
     *
     * @param string $role Role name (owner, read-write, read-only, default)
     * @param Array  $permissions List of permissions in Clark notation
     * @throws \RuntimeException If the role was already configured
     */
    public function setPrivilegesFor($role, Array $permissions)
    {
        if (isset($this->list[$role])) {
            throw new \RuntimeException('Privilege set for ' . $role . ' already defined');
        }

        $this->list[$role] = $permissions;
    }

    /**
     * Gets the set of privileges configured for a given role
     *
     * @param string $role Role name
     * @return array List of privileges in Clark notation
     * @throws \RuntimeException If role is not configured
     */
    public function getPrivilegesFor($role)
    {
        if (!isset($this->list[$role])) {
            throw new \RuntimeException('Privilege set for ' . $role . ' not defined');
        }

        return $this->list[$role];
    }

    /**
     * Returns privileges for all configured roles
     */
    public function getAll()
    {
        return $this->list;
    }
}
