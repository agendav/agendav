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

class Userpref extends CI_Model {
	function __construct() {
		parent::__construct();

	}

	/**
	 * Gets all user preferences for a given user into a Preferences object
	 */
	function load_prefs($username) {
		$options = array();

		$query = $this->db->get_where('prefs',
				array('username' => $username));

		if ($query->num_rows() == 1) {
			$result = $query->result();
			$options = json_decode($result[0]->options, TRUE);
		}

		$prefs = new Preferences($options);

		return $prefs;
	}

	/**
	 * Saves user preferences
	 */
	function save_prefs($username, Preferences $prefs) {
		$data = array(
				'options' => $prefs->to_json(),
				);

		log_message('DEBUG', 'Storing user prefs ['.
				$prefs->to_json() .']'
				.' for user ' . $username);

		$query = $this->db->get_where('prefs',
				array('username' => $username));
		if ($query->num_rows() == 1) {
			$this->db->update('prefs',
					$data, array('username' => $username));
		} else {
			$data['username'] = $username;
			$this->db->insert('prefs', $data);
		}
	}
}

