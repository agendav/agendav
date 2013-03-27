<?php

namespace AgenDAV\Data;

/*
 * Copyright 2013 Jorge López Pérez <jorge@adobo.org>
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
 * Stores permissions information
 */
class Permissions
{
    /**
     * Current permissions
     *
     * @var Array
     */
    private $perms;

    /**
     * Default permissions
     *
     * @var Array
     */
    private $default_perms;

    public function __construct(Array $default_perms)
    {
        $this->checkValidPermissionsArray($default_perms);
        $this->default_perms = $default_perms;
        $this->perms = array();
    }

    /**
     * Adds a new profile
     *
     * @param string $name
     * @param Array  $perms
     */
    public function addProfile($name, Array $perms)
    {
        if (isset($this->perms[$name])) {
            throw new \RuntimeException('Profile ' . $name . ' already defined');
        } else {
            $this->checkValidPermissionsArray($perms);
            $this->perms[$name] = $perms;
        }
    }

    public function getProfile($name)
    {
        if (!isset($this->perms[$name])) {
            throw new \RuntimeException('Profile ' . $name . ' not defined');
        } else {
            return $this->perms[$name];
        }
    }

    public function getDefault()
    {
        return $this->default_perms;
    }

    private function checkValidPermissionsArray($arr)
    {
        foreach ($arr as $elem) {
            if (!($elem instanceof SinglePermission)) {
                throw new \RuntimeException(
                    'Invalid element ' . var_export($elem, true) . ' inside '
                    .'permissions array'
                );
            }
        }
        return true;
    }
}
