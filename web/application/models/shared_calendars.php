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

class Shared_calendars extends CI_Model {
	function __construct() {
		parent::__construct();

	}

	/**
	 * Get calendars which other users are sharing with a given user.
	 *
	 * @return array		Array of calendars (sid, user_from, calendar)
	 */
	function get_shared_with($username) {
		$res = $this->db->get_where('shared', array(
					'user_which' => $username));

		$tmp = $res->result_array();
		$result = array();

		foreach ($tmp as $c) {
			$index = $c['user_from'] .':'. $c['calendar'];
			$result[$index] = array(
					'sid' => $c['sid'],
					'user_from' => $c['user_from'],
					);
			$options = unserialize($c['options']);
			if (is_array($options)) {
				$result[$index] = array_merge($result[$index], $options);
			}
		}

		return $result;
	}

	/**
	 * Get calendars which current user is sharing with other users
	 *
	 * @param $username		User which is sharing calendars
	 * @param $cond			Additional conditions for this query (optional).
	 * 						Associative array
	 * @return array		Array of calendars (sid, user_from, calendar)
	 */
	function get_shared_from($username, $cond = null) {
		$conditions = array('user_from' => $username);
		if (!is_null($cond) && is_array($cond)) {
			$conditions = array_merge($conditions, $cond);
		}
		$res = $this->db->get_where('shared', $conditions);

		$tmp = $res->result_array();
		$result = array();

		foreach ($tmp as $c) {
			$index = $c['user_from'] .':'. $c['calendar'];
			if (!isset($result[$index])) {
				$result[$index] = array();
			}
			$sid = $c['sid'];

			$options = unserialize($c['options']);
			if (is_array($options)) {
				$c = array_merge($c, $options);
			}

			$result[$index][$sid] = $c;
		}

		return $result;
	}

	/**
	 * Get a list of users which an user can access
	 *
	 * @param $calendar		Complete calendar name (user:calendar)
	 * @param $as_string	Return results as a comma separated string
	 * 						(user1,user2,...)
	 * @return				Associative array of the form:
	 *						 ('username' => sid, 'username2' => sid2, ...)
	 *						If $as_string was set as TRUE, then a comma
	 *						separated string just with the names is returned
	 *
	 */

	function users_with_access_to($calendar, $as_string = FALSE) {
		$pieces = preg_split('/:/', $calendar);
		if (count($pieces) != 2) {
			log_message('ERROR', 'Call to users_with_access_to() '
					.'without full calendar specified (' . $calendar .')');
			return ($as_string ? '' : array());
		}

		$user_from = $pieces[0];
		$calendar = $pieces[1];

		$tmp = $this->get_shared_from($user_from, array(
					'calendar' => $calendar));
		$users = array();

		if (count($tmp) > 0) {
			$tmp = array_values($tmp);
			foreach ($tmp[0] as $sid => $share) {
				$users[$share['user_which']] = $share['sid'];
			}

			ksort($users);
		}

		return ($as_string ? implode(',', array_keys($users)) : $users);
	}

	/**
	 * Store a shared calendar.
	 *
	 * @param $sid	Share id. Null means a new calendar sharing
	 * @param $from	User id who is sharing a calendar
	 * @param $calendar	Calendar being shared. Can be in the form
	 *	 'user:calendar'
	 * @param $to	User id who's getting calendar rights
	 * @param $options	Associative array with options for this calendar
	 *   (color, displayname, ...)
	 * @return boolean	FALSE on error, TRUE otherwise
	 */
	function store($sid = null, $from = '', $calendar = '', $to = '', $options = array()) {
		if (empty($from) || empty($calendar) || empty($to)) {
			log_message('ERROR', 
					'Call to shared_calendars->store() with no enough parameters');
			return FALSE;
		}

		$calendar = preg_replace('/^'.$from.':/', '', $calendar);

		$data = array(
				'user_from' => $from,
				'calendar' => $calendar,
				'user_which' => $to,
				'options' => serialize($options),
				);

		$res = false;
		if (!is_null($sid)) {
			$conditions = $data;
			unset($conditions['options']);
			$conditions['sid'] = $sid;
			$data = array('options' => serialize($options));

			$this->db->where($conditions);
			$res = $this->db->update('shared', $data);
		} else {
			$res = $this->db->insert('shared', $data);

			if ($res === TRUE) {
				log_message('INTERNALS', 'Granted access on '
						. $data['user_from'] . ':' . $data['calendar'] . ' to '
						. $data['user_which']);
			} else {
				log_message('ERROR', 'Error granting access on '
						. $data['user_from'] . ':' . $data['calendar'] . ' to '
						. $data['user_which'] . ', SQL error');
			}
		}


		return $res;
	}

	/**
	 * Removes a sharing from database
	 *
	 * @param $sid	Sharing id
	 * @return boolean	FALSE on error, TRUE otherwise
	 */
	function remove($sid = null) {
		if (is_null($sid)) {
			log_message('ERROR',
					'Call to shared_calendars->remove() without sid');
			return FALSE;
		}

		$this->db->where('sid', $sid);
		$query = $this->db->get('shared');
		$row = $query->result_array();

		if (count($row) == 0) {
			log_message('ERROR', 
					'Tried to remove nonexistant share id [' . $sid .']');
			return FALSE;
		} else {
			$row = $row[0];
			$this->db->where('sid', $sid);
			$this->db->delete('shared');

			log_message('INTERNALS', 'Revoked access on '
					. $row['user_from'] . ':' . $row['calendar'] . ' to '
					. $row['user_which']);
			return TRUE;
		}
				
	}

}

