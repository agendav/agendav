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

class Caldav {
	var $final_url;
	var $CI;
	var $client = null;

	function __construct($params) {

		$this->CI =& get_instance();
		// Load CalDAV settings
		$this->CI->config->load('caldav');

		// Light loading, for using some functions without loading the full
		// stack
		if (!isset($params['light']) || $params['light'] !== TRUE) {
			// Load ICS helper library
			$this->CI->load->library('icshelper');

			// Add required paths
			$current_include_path = get_include_path();
			set_include_path($current_include_path . ':' 
					. APPPATH . '../../libs/awl/inc:' 
					. APPPATH . '../../libs/own_extensions:' 
					. APPPATH . '../../libs/davical/inc');

			require_once('caldav-client-v2.php');
			require_once('mycaldav.php');
			require_once('iCalendar.php');
		}

	}

	/**
	 *
	 * Returns FALSE on error
	 */
	function fetch_expanded_events( $user, $passwd, $start, $end,
						$calendar = 'calendario') {
		$this->prepare_client($user, $passwd, $calendar);

		$events = $this->client->GetExpandedEvents($start, $end);

		$this->CI->extended_logs->message('INTERNALS', 'There are ' 
				.  count($events) . ' event(s)');

		return $events;
	}

	/**
	 *
	 * Returns FALSE on error
	 */
	function fetch_events( $user, $passwd, $start, $end,
						$calendar = 'calendario') {
		$this->prepare_client($user, $passwd, $calendar);

		$events = $this->client->GetEvents($start, $end);

		// Bogus CalDAV server
		if ($events === FALSE) {
			$this->CI->extended_logs->message('ERROR', 
					'Possible invalid CalDAV server');
		} else {
			$this->CI->extended_logs->message('INTERNALS', 'Received ' 
					.  count($events) . ' event(s)');
		}

		return $events;
	}

	/**
	 *
	 * Returns FALSE on error, null if no event was found
	 */
	function fetch_resource_by_uid( $user, $passwd, $uid,
						$calendar = 'calendario') {
		$this->prepare_client($user, $passwd, $calendar);

		$resource = array_values($this->client->GetEntryByUid($uid));

		if (!is_array($resource) || count($resource) == 0) {
			$this->CI->extended_logs->message('INTERNALS', 
					'Search for uid=' . $uid 
					. ' on calendar ' . $calendar 
					. ' failed or returned more than one element '
					.'['.count($resource).']');
			return null;
		} else {
			return $resource[0];
		}
	}

	/**
	 * Deletes a resource
	 *
	 * Returns TRUE on success, or an error message on failure
	 */

	function delete_resource( $user, $passwd, $href,
						$calendar = 'calendario',
						$etag = null) {

		$this->prepare_client($user, $passwd, $calendar);

		$resource = $this->final_url . $href;

		$res = $this->client->DoDELETERequest($resource, $etag);

		// Did this operation succeed?
		switch ($res) {
			case '404':
				return 'Elemento no encontrado';
				break;
			case '412':
				return 'El evento sufrió cambios en este rato. '
					.'Refresque antes de eliminarlo';
				break;
			case '204':
				// Success
				return TRUE;
			default:
				return "Código HTTP " . $res;
				break;
		}

	}

	/**
	 * Puts a iCalendar resource (iCalComponent object)
	 * 
	 * Returns etag of new resource, or FALSE if failed
	 *
	 * Use etag = '*' when adding new resources
	 */

	function put_resource( $user, $passwd, $href,
						$calendar = 'calendario',
						$icalendar,
						$etag = null) {

		$this->prepare_client($user, $passwd, $calendar);

		// Avoid strange problems with empty href and empty calendars
		if (!isset($href) || empty($href)) {
			$this->CI->extended_logs->message('ERROR', 'Discarding PUT'
					.' attempt without href specified');
			return FALSE;
		}
		$url = $this->final_url . $href;
		$ical_text = $icalendar->createCalendar();
		$new_etag = $this->client->DoPUTRequest($url, $ical_text, $etag);

		switch ($this->client->GetHTTPResultCode()) {
			case '412':
				// ETag match failed
				$this->CI->extended_logs->message('INTERNALS',
						'PUT with ETag=' . $etag . ' failed on '
						.$url . '. Precondition failed.');
				return FALSE;
			case '201':
			case '204':
				// All right
				$this->CI->extended_logs->message('INTERNALS',
						'Successful PUT with ETag=' . $etag . ' on '
						. $url);
				return $new_etag;
			default:
				$this->CI->extended_logs->message('INTERNALS',
						'PUT with ETag=' . $etag . ' on '
						. $url . ' returned ' .
						$this->client->GetHttpResultCode());
				return FALSE;
		}

	}


	/**
	 * Constructs the full CalDAV URL and client
	 */
	function prepare_client($user, $passwd, $calendar = 'calendario') {
		$this->final_url = $this->build_url($user, $calendar);

		$this->client = new MyCalDAV($this->final_url, $user, $passwd);
		$this->client->SetCalendar($this->final_url);
		$this->client->PrincipalURL($this->final_url);
		$this->client->CalendarHomeSet($this->final_url);
	}

	/**
	 * Is this a valid calendar resource?
	 */
	function is_valid_calendar($user, $passwd, $calendar) {
		$this->prepare_client($user, $passwd, $calendar);
		$url = $this->build_url($user, $calendar);
		$info = $this->client->GetCalendarDetailsByURL($url);

		if ($this->client->GetHttpResultCode() != '207') {
			// Resource not found (404) or no enough permissions (403)
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Check for correct authentication
	 */

	function check_server_authentication($user, $passwd) {
		$this->prepare_client($user, $passwd, '');
		return $this->client->CheckValidCalDAV();
	}

	/**
	 * Gets last HTTP response in array form:
	 * [http result code, http response headers, http response body]
	 */

	function get_last_response() {
		if (is_null($this->client)) {
			return array ('', '', '');
		} else {
			return array(
					$this->client->GetHttpResultCode(),
					$this->client->GetResponseHeaders(),
					$this->client->GetResponseBody(),
					);
		}
	}

	/**
	 * Get a list of calendars owned by current user
	 */
	function get_own_calendars($user, $passwd) {
		$this->prepare_client($user, $passwd, '');

		$tmpcals =  $this->client->FindCalendars();
		$result = array();

		return $this->prepare_calendar_data_for_browser($tmpcals);
	}

	/**
	 * Get the properties of a calendar list
	 *
	 * @return Prepared data for browser, FALSE on error
	 */
	function get_shared_calendars_info($user, $passwd, $calendar_list) {
		$this->prepare_client($user, $passwd, '');
		
		$tmpcals = array();
		foreach ($calendar_list as $calid => $contents) {
			$url = $this->build_url($user, $calid);
			$info = $this->client->GetCalendarDetailsByURL($url);
			//$info->color = ...

			if (!is_array($info) || count($info) == 0) {
				// Something went really wrong
				$this->CI->extended_logs->message('ERROR', 
						'PROPFIND on ' . $url . ' failed');
				return FALSE;
			}


			// Give priority to previous data (user customizations?)
			$preserve = array('sid', 'shared', 'user_from', 'color',
					'displayname');
			foreach ($preserve as $p) {
				if (isset($contents[$p])) {
					$info[0]->$p = $contents[$p];
				}
			}

			$info[0]->shared = TRUE;
			$tmpcals[$calid] = $info[0];
		}

		return $this->prepare_calendar_data_for_browser($tmpcals);
	}

	/**
	 * Creates a new calendar inside a principal collection
	 *
	 * @return boolean	TRUE on successful creation, error message otherwise
	 */

	function mkcalendar( $user, $passwd, $calendar = '',
						$props = array()) {

		$this->prepare_client($user, $passwd, '');

		// Empty calendar?
		if (empty($calendar)) {
			$this->CI->extended_logs->message('ERROR', 'Call to mkcalendar()'
					.' with no calendar!');
			return 'Falta nombre interno del calendario';
		}
		
		if (!isset($props['displayname'])) {
			$this->CI->extended_logs->message('ERROR', 'Call to mkcalendar()'
					.' with no displayname!');
			return 'Falta nombre del calendario';
		}

		if (!isset($props['color'])) {
			$this->CI->extended_logs->message('ERROR', 'Call to mkcalendar()'
					.' with no color!');
			return 'Falta el color del calendario';
		}

		$url = $this->final_url . $calendar;

		// Create XML body
		$ns = array(
				'DAV:' => '', 
				'urn:ietf:params:xml:ns:caldav' => 'C',
				'http://apple.com/ns/ical/' => 'ical');
		$xml = new XMLDocument($ns);
		$set = $xml->NewXMLElement('set');
		$prop = $set->NewElement('prop');
		$xml->NSElement($prop, 'displayname', $props['displayname']);
		$xml->NSElement($prop, 
				'http://apple.com/ns/ical/:calendar-color',
				$props['color']);

		// TODO: associate timezone? AWL doesn't like <CDATA, 
		// gets replaced by html entity
		
		$xml_text = $xml->Render('C:mkcalendar',
				$set, null, 'http://apple.com/ns/ical/:calendar-color');

		$res = $this->client->DoXMLRequest('MKCALENDAR', 
				$xml_text, $url);

		switch ($this->client->GetHTTPResultCode()) {
			case '201':
				// OK
				$this->CI->extended_logs->message('INTERNALS',
						'Calendar ' . $calendar . ' successfully created');
				return TRUE;
			case '207':
				// Error on parameters
				$this->CI->extended_logs->message('INTERNALS',
						'Calendar '.$calendar.' not created.'
						.' Error 207, invalid parameter(s)');
				return 'El servidor rechazó la creación (revise'
						.' los parámetros)';
			case '403':
				// Permission denied
				$this->CI->extended_logs->message('INTERNALS',
						'Calendar '.$calendar.' not created.'
						.' Error 403, access forbidden');
				return 'El servidor denegó la petición (contacte con'
						.' soporte)';
			default:
				$this->CI->extended_logs->message('INTERNALS',
						'Calendar '.$calendar.' not created.'
						.' Error '
						. $this->client->GetHttpResultCode());
				return 'Error ' . $this->client->GetHTTPResultCode();
		}
	}

	/**
	 * Applies a properties change to a DAV resource
	 *
	 * @return boolean	TRUE on successful creation, error message otherwise
	 */
	function proppatch( $user, $passwd, $calendar = '',
						$props = array()) {
		$this->prepare_client($user, $passwd, '');

		// Empty calendar?
		if (empty($calendar)) {
			$this->CI->extended_logs->message('ERROR', 'Call to proppatch()'
					.' with no calendar!');
			return 'Falta nombre interno del calendario';
		}
		
		if (!isset($props['displayname'])) {
			$this->CI->extended_logs->message('ERROR', 'Call to proppatch()'
					.' with no displayname!');
			return 'Falta nombre del calendario';
		}

		if (!isset($props['color'])) {
			$this->CI->extended_logs->message('ERROR', 'Call to proppatch()'
					.' with no color!');
			return 'Falta el color del calendario';
		}

		$url = $this->final_url . $calendar;

		// Create XML body
		$ns = array(
				'DAV:' => '', 
				'urn:ietf:params:xml:ns:caldav' => 'C',
				'http://apple.com/ns/ical/' => 'ical');
		$xml = new XMLDocument($ns);
		$set = $xml->NewXMLElement('set');
		$prop = $set->NewElement('prop');
		$xml->NSElement($prop, 'displayname', $props['displayname']);
		$xml->NSElement($prop, 
				'http://apple.com/ns/ical/:calendar-color',
				$props['color']);

		// TODO: associate timezone? AWL doesn't like <CDATA, 
		// gets replaced by html entity
		
		$xml_text = $xml->Render('propertyupdate',
				$set, null, 'http://apple.com/ns/ical/:calendar-color');

		$res = $this->client->DoXMLRequest('PROPPATCH', 
				$xml_text, $url);

		switch ($this->client->GetHTTPResultCode()) {
			case '200':
				// OK
				$this->CI->extended_logs->message('INTERNALS',
						'Calendar ' . $calendar . ' successful modification');
				return TRUE;
			default:
				$this->CI->extended_logs->message('INTERNALS',
						'Calendar '.$calendar.' not modified.'
						.' Error '
						. $this->client->GetHttpResultCode());
				return 'Error ' . $this->client->GetHTTPResultCode();
		}
	}

	/**
	 * Sets ACL on a resource
	 *
	 * @return boolean	TRUE on successful creation, error message otherwise
	 */
	function setacl( $user, $passwd, $calendar = '',
						$share_with = array()) {
		$this->prepare_client($user, $passwd, '');

		// Empty calendar?
		if (empty($calendar)) {
			$this->CI->extended_logs->message('ERROR', 'Call to setacl()'
					.' with no calendar!');
			return 'Falta nombre interno del calendario';
		}
		
		$url = $this->final_url . $calendar;

		// Create XML body
		$xmlbody = $this->generate_acl_xml($share_with);
		if ($xmlbody === FALSE) {
			$this->CI->extended_logs->message('ERROR', 'Call to setacl()'
					.' generated invalid XML code. Giving up.');
			return 'Error interno de generación de permisos';
		}

		$res = $this->client->DoXMLRequest('ACL', 
				$xmlbody, $url);

		switch ($this->client->GetHTTPResultCode()) {
			case '200':
				// OK
				$this->CI->extended_logs->message('INTERNALS',
						'Successful modification of ACL for calendar ' 
						. $calendar);
				return TRUE;
			default:
				$this->CI->extended_logs->message('INTERNALS',
						'ACL for calendar '.$calendar.' not modified.'
						.' Error '
						. $this->client->GetHttpResultCode());
				return 'Error ' . $this->client->GetHTTPResultCode()
					.'. Posiblemente algún usuario de los indicados no exista';
		}
	}

	/**
	 * Returns the public CalDAV URL for a calendar
	 *
	 * @param	$calendar	String in the form 'user:calendar', or just
	 *                      'user'
	 */
	function construct_public_url($calendar = '') {
		$calendar = preg_replace('/:/', '/', $calendar);
		$url = preg_replace('/%s/', $calendar,
				$this->CI->config->item('public_caldav_url'));

		return $url;
	}


	/**
	 * Converts a RGB hexadecimal string (#rrggbb or short #rgb) to full
	 * RGBA
 	 */
	function _rgb2rgba($s) {
		if (strlen($s) == '7') {
			return $s . 'ff';
		} elseif (strlen($s) == '4') {
			$res = preg_match('/#(.)(.)(.)/', $s, $matches);
			return '#' . $matches[1] . $matches[1] . $matches[2] .
				$matches[2] . $matches[3] . $matches[3] . 'ff';
		} else {
			// Unknown string
			return $s;
		}
	}

	/**
	 * Converts a RGBA hexadecimal string (#rrggbbXX) to RGB
 	 */
	function _rgba2rgb($s) {
		if (strlen($s) == '9') {
			return substr($s, 0, 7);
		} else {
			// Unknown string
			return $s;
		}
	}

	/**
	 * Parses calendar information to be sent to the browser
	 *
	 * $calendars is an array of CalendarExtendedInfo objects
	 */
	function prepare_calendar_data_for_browser($calendars) {
		$result = array();
		foreach ($calendars as $c) {
			$result[$c->calendar] = get_object_vars($c);

			// Shorten calendar displayname if needed
			$dn = $result[$c->calendar]['displayname'];
			$result[$c->calendar]['shown_displayname'] =
				(strlen($dn) > 20) ? 
				substr($dn, 0, 20) . '...' : $dn;

			// Adapt color
			$result[$c->calendar]['color'] =
				$this->_rgba2rgb($c->color);
		}

		return $result;
	}


	/**
	 * Generates a complete ACL to be set on a calendar
	 *
	 * @param $share_with	Array of user identifiers who will have access
	 * 						to this calendar
	 *
	 * @return	boolean		TRUE if everything went ok, FALSE otherwise
	 */
	function generate_acl_xml($share_with = array()) {
		$ns = array(
				'DAV:' => '', 
				'urn:ietf:params:xml:ns:caldav' => 'C',
				);
		$xml = new XMLDocument($ns);
		$aces = array();

		// Relative URL
		$parsed = @parse_url($this->CI->config->item('caldav_url'));
		if ($parsed === FALSE) {
			return FALSE;
		} else {
			$rel_url_template = $parsed['path'];
		}

		// Permissions
		$owner_perm = $this->CI->config->item('owner_permissions');
		$share_perm = $this->CI->config->item('share_permissions');
		$other_perm = $this->CI->config->item('default_permissions');

		// Owner permissions
		$aces[] = $this->_ace_for($xml, FALSE, $owner_perm, TRUE);

		// User which can access this calendar
		foreach ($share_with as $user) {
			$user_url = preg_replace('/%u/', $user, $rel_url_template);
			$aces[] = $this->_ace_for($xml, $user_url, $share_perm);
		}

		// Other users
		$aces[] = $this->_ace_for($xml, FALSE, $other_perm, FALSE, TRUE);

		return $xml->Render('acl', $aces);
	}

	/**
	 * Generates an ACE element
	 */
	function _ace_for(&$xmldoc, $user, $perms = array(), $owner = FALSE, $other =
			FALSE) {
		$ace = $xmldoc->NewXMLElement('ace');
		$principal = $ace->NewElement('principal');
		if ($owner === TRUE) {
			$principal->NewElement('property')->NewElement('owner');
		} elseif ($other === TRUE) {
			$principal->NewElement('authenticated');
		} else {
			$principal->NewElement('href', $user);
		}

		$grant = $ace->Newelement('grant');
		foreach ($perms as $p) {
			$grant->NewElement('privilege')->NewElement($p);
		}

		return $ace;
	}


	/**
	 * Builds an URL based on $user and $calendar.
	 * $calendar can contain 'user:calendar'
	 */
	function build_url($user, $calendar) {
		$base_url = $this->CI->config->item('caldav_url');

		$pieces = preg_split('/:/', $calendar);
		if (count($pieces) == '1') {
			$use_principal = $user;
		} else {
			$use_principal = $pieces[0];
			$calendar = $pieces[1];
		}

		return preg_replace('/%u/', $use_principal, $base_url) .
			$calendar . '/';
	}


}

