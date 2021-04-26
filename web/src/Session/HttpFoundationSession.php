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

use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\HttpFoundation\Session\Session as InternalSession;

/**
 * HttpFoundationSession
 *
 * Implements Http Foundation session manager using Session interface
 *
 * @uses ISessionManager
 */
class HttpFoundationSession implements Session
{

    /**
     * Actual Http Foundation session
     */
    private $session;

    /**
     * @param SessionStorageInterface $storage  Symfony storage to use
     */
    public function __construct(SessionStorageInterface $storage)
    {
        $this->session = new InternalSession($storage);
    }

    /**
     * Gets a session variable value
     *
     * @param string $name Session variable
     * @access public
     * @return mixed If variable was not found, returns null
     */
    public function initialize()
    {
    }

    /**
     * Gets a session variable value
     *
     * @param string $name Session variable
     * @access public
     * @return mixed If variable was not found, returns null
     */
    public function get($name)
    {
        return $this->session->get($name);
    }

    /**
     * Sets a session variable
     *
     * @param string $name Session variable
     * @param mixed $value Value
     * @access public
     * @return void
     */
    public function set($name, $value)
    {
        return $this->session->set($name, $value);
    }

    /**
     * Sets multiple session variables
     *
     * @param Array $data Associative array: name => value
     * @access public
     * @return void
     */
    public function setAll($data)
    {
        $this->session->replace($data);
    }

    /**
     * Checks if current session contains a variable
     *
     * @param string $name
     * @access public
     * @return boolean
     */
    public function has($name)
    {
        return $this->session->has($name);
    }

    /**
     * Removes a session variable from current session
     *
     * @param string $name
     * @access public
     * @return void
     */
    public function remove($name)
    {
        return $this->session->remove($name);
    }

    /**
     * Checks if current user is authenticated
     *
     * @access public
     * @return boolean  true if user is authenticated, false if not
     */
    public function isAuthenticated()
    {
        return $this->session->has('username') &&
            $this->session->has('password');
    }

    /**
     * Clears current session
     *
     * @access public
     * @return void
     */
    public function clear()
    {
        $this->session->clear();
    }
}
