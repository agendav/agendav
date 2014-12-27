<?php
namespace AgenDAV\CalDAV\Share;

use AgenDAV\CalDAV\Share\Permissions;

/*
 * Copyright 2013-2014 Jorge López Pérez <jorge@adobo.org>
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

class ACL
{

    /**
     * Privileges storage
     *
     * @var AgenDAV\CalDAV\Share\Permissions
     */
    private $permissions;

    /**
     * Current grants
     *
     * @var Array
     */
    private $grants;

    /**
     * Creates a new ACL
     *
     * @param AgenDAV\CalDAV\Share\Permissions $permissions
     */
    public function __construct(Permissions $permissions)
    {
        $this->permissions = $permissions;
        $this->grants = [];
    }

    /**
     * Adds a new grant to this ACL
     *
     * @param string $principal Principal path
     * @param string $role Principal role (e.g. owner, read-write, read-only, default)
     * @throws \InvalidArgumentException if role is owner or default
     * @throws \RuntimeException if grant for $principal was already set
     */
    public function addGrant($principal, $role)
    {
        if ($role === 'owner' || $role === 'default') {
            throw new \InvalidArgumentException('Forbidden ACL grant with role: ' . $role);
        }

        if (isset($this->grants[$principal])) {
            throw new \RuntimeException('ACL grant already set for ' . $principal);
        }

        $this->grants[$principal] = $role;
    }


    /**
     * Gets a list of grants provided by this ACL
     *
     * @return array
     */
    public function getGrants()
    {
        return $this->grants;
    }

    /**
     * Returns a list of privileges configured for the owner of the resource
     * affected by this ACL
     *
     * @return array
     */
    public function getOwnerPrivileges()
    {
        return $this->permissions->getPrivilegesFor('owner');
    }

    /**
     * Returns a list of default privileges configured for the resource affected
     * by this ACL
     *
     * @return array
     */
    public function getDefaultPrivileges()
    {
        return $this->permissions->getPrivilegesFor('default');
    }

    /**
     * Gets a list of privileges given to granted principals
     *
     * @return Array Associative array, where keys = owner/default/{principal-URL}
     *               and values are arrays of privileges in Clark notation
     */
    public function getGrantsPrivileges()
    {
        $result = [];

        foreach ($this->grants as $principal => $role) {
            $result[$principal] = $this->permissions->getPrivilegesFor($role);
        }

        return $result;
    }
}
