<?php 
/*
 * Copyright 2011-2012 Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\Session\Session;

/**
 * Represents the current AgenDAV user 
 */
class User
{
    /**
     * User name 
     *
     * @var string
     */
    private $username;

    /**
     * Password provided by the user 
     *
     * @var string
     * @access private
     */
    private $passwd;

    /**
     * Additional user properties 
     *
     * @var mixed
     * @access private
     */
    private $properties;

    /**
     * Whether current user is authenticated or not 
     *
     * @var boolean
     * @access private
     */
    private $is_authenticated = false;

    /**
     * Session manager 
     *
     * @var Session
     * @access private
     */
    private $session = null;

    /**
     * Encryption manager 
     *
     * @var Object
     * @access private
     */
    private $encrypt;

    /**
     * Creates a user instance. Loads data from session, if available
     *
     * @param Session $session Session manager
     * @param Object $encrypt Encryption manager
     * @access public
     * @return void
     */
    public function __construct(Session $session, $encrypt) {
        $this->session = $session;
        $this->encrypt = $encrypt;

        // Initialize session
        $this->session->initialize();
        
        // TODO other properties!
        foreach (array('username', 'passwd', 'is_authenticated') as $n) {
            if (null !== $current = $this->session->get($n)) {

                // Decrypt password
                if ($n == 'passwd') {
                    $current = $this->encrypt->decode($current);
                }

                $this->$n = $current;
            }
        }
    }

    /**
     * Set user credentials
     *
     * @param string $username User name
     * @param string $passwd Clear text password
     * @return void
     */
    public function setCredentials($username, $passwd) {
        $this->username = mb_strtolower($username);
        $this->passwd = $passwd;
    }

    /**
     * Gets current user name
     *
     * @return string User name
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Gets user password
     *
     * @return string Password
     */
    public function getPasswd() {
        return $this->passwd;
    }

    // TODO other properties!

    /**
     * Creates new session
     *
     * @return void
     */
    public function newSession() {
        $data = array(
                'username' => $this->username,
                'passwd' => $this->encrypt->encode($this->passwd),
                'is_authenticated' => $this->is_authenticated,
                );
        $this->session->setAll($data);
    }

    /**
     * Empty current session
     *
     * @return void
     */
    public function removeSession() {
        $this->session->clear();
    }

    /**
     * Checks valid authentication against CalDAV server
     *
     * @return boolean Current user is logged in
     */
    public function isAuthenticated() {
        if (empty($this->username) || empty($this->passwd)) {
            return false;
        } else {
            return $this->is_authenticated;
        }
    }

    /**
     * Sets current user authentication status
     *
     * @param bool $is_authenticated 
     * @return void
     */
    public function setAuthenticated($is_authenticated)
    {
        $this->is_authenticated = $is_authenticated;
    }

}
