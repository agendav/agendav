<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

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


/**
 * This class will create and cache DateTimeZone objects
 */

class Timezonemanager {
	private $timezones;

	function __construct() {
		$this->timezones = array();
	}

	public function getTz($name) {
		return (isset($this->timezones[$name])) ?
			$this->timezones[$name] :
			$this->createTz($name);
	}

	private function createTz($name) {
		try {
			$tz = new DateTimeZone($name);
		} catch (Exception $e) {
			// Invalid timezone
			return FALSE;
		}

		$this->timezones[$name] = $tz;
		return $tz;
	}
}
