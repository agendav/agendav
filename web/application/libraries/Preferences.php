<?php 

namespace Data;

if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

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


class Preferences {
    private $options = array();

    function __construct($arr_values = array()) {
        foreach($arr_values as $name => $value) {
            $this->options[$name] = $value;
        }
    }

    function __set($name, $value) {
        $this->options[$name] = $value;
    }

    function __get($name) {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        } else {
            return null;
        }
    }

    function getAll() {
        return $this->options;
    }

    function setAll($options) {
        $this->options = $options;
    }

    function to_json() {
        return json_encode($this->options);
    }
}
