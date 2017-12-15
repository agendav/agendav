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

namespace AgenDAV\Session;

interface Session
{
    /**
     * Initializes session manager
     *
     * @access public
     * @return void
     */
    public function initialize();

    /**
     * Gets a session variable value
     *
     * @param string $name Session variable
     * @access public
     * @return mixed If variable was not found, returns null
     */
    public function get($name);

    /**
     * Sets a session variable
     *
     * @param string $name Session variable
     * @param mixed $value Value
     * @access public
     * @return void
     */
    public function set($name, $value);

    /**
     * Sets multiple session variables
     *
     * @param array $data Associative array: name => value
     * @access public
     * @return void
     */
    public function setAll($data);

    /**
     * Checks if current session contains a variable
     *
     * @param string $name
     * @access public
     * @return boolean
     */
    public function has($name);

    /**
     * Removes a session variable from current session
     *
     * @param string $name
     * @access public
     * @return void
     */
    public function remove($name);

    /**
     * Checks if current user is authenticated
     *
     * @access public
     * @return boolean  true if user is authenticated, false if not
     */
    public function isAuthenticated();

    /**
     * Clears current session
     *
     * @access public
     * @return void
     */
    public function clear();
}
