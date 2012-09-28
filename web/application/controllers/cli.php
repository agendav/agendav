<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

/*
 * Copyright 2011 Jorge López Pérez <jorge@adobo.org>
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

class Cli extends MY_Controller {

    // Special CLI controller

    private static $usage = <<<EOU
Available commands:

    dbupdate        Updates database schema

EOU;

    function __construct() {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            echo "This controller can only be run from CLI";
            die();
        }
    }

    function index() {
        $this->_print(self::$usage);
    }

    function help() {
        $this->_print(self::$usage);
    }

    function dbupdate() {
        $this->_print('Updating database schema...');
        $this->load->library('migration');
        if (!$this->migration->current()) {
            $this->_print('Error while updating schema!');
            $this->_print($this->migration->error_string());
        } else {
            $this->_print('Succeed! Your database is updated');
        }
    }


    /**
     * Print function
     */
    function _print($text) {
        echo preg_replace(array(
                    '/\n/',
                    '/([^\n])$/'),
                array(
                    PHP_EOL,
                    '\1' . PHP_EOL),
                $text);
    }

}
