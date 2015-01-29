<?php

namespace AgenDAV\Data;

/*
 * Copyright 2012 Jorge López Pérez <jorge@adobo.org>
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
 * Holds user preferences
 */

/**
 * @Entity
 * @Table(name="prefs")
 */
class Preferences
{
    /** @Id @Column(type="string") */
    private $username;

    /** @Column(type="json_array") */
    private $options = array();

    public function __construct($arr_values = array()) {
        foreach($arr_values as $name => $value) {
            $this->options[$name] = $value;
        }
    }

    public function __set($name, $value) {
        $this->options[$name] = $value;
    }

    public function __get($name) {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        } else {
            return null;
        }
    }

    /**
     * Gets an user preference
     *
     * @param string $name Preference name
     * @param mixed $default_value Default value if preference is not set
     * @return mixed Preference value, or default_value if it is not set
     */
    public function get($name, $default_value = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default_value;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getAll() {
        return $this->options;
    }

    public function setAll($options) {
        $this->options = $options;
    }

    /**
     * Sets default values for usual preferences. If a preference already
     * has a value, it will not get overwritten
     *
     * @param array $defaults
     * @return void
     */
    public function addDefaults(array $defaults)
    {
        foreach ($defaults as $name => $default_value) {
            if (isset($this->options[$name])) {
                continue;
            }

            $this->options[$name] = $default_value;
        }
    }

    public function to_json() {
        return json_encode($this->options);
    }
}
