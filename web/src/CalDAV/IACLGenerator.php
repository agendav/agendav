<?php
namespace AgenDAV\CalDAV;

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

interface IACLGenerator
{
    /**
     * Adds a new grant to this ACL
     *
     * @param string $principal Principal href
     * @param string $profile Profile (owner, read, read_write)
     * @throws \RuntimeException if profile is invalid
     * @return boolean Grant failed
     */
    public function addGrant($principal, $profile);

    /**
     * Builds ACL for current added users
     *
     * @return string XML DAV ACL
     */
    public function buildACL();
}
