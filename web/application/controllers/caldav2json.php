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

class Caldav2json extends CI_Controller {

	private $time_format;
	private $date_format;

	private $tz;
	private $calendar_colors;

	function __construct() {
		parent::__construct();

		if (!$this->auth->is_authenticated()) {
			$this->extended_logs->message('INFO', 
					'Anonymous access attempt to '
					. uri_string());
			$this->output->set_status_header('401');
			$this->output->_display();
			die();
		}

		$this->date_format = $this->dates->date_format_string('date');
		$this->time_format = $this->dates->time_format_string('date');

		$this->tz = $this->config->item('default_timezone');

		$this->calendar_colors = $this->config->item('calendar_colors');

		$this->load->library('caldav');

		$this->output->set_content_type('application/json');
	}

	function index() {
	}

	function events() {
		$returned_events = array();
		$err = 0;

		// For benchmarking
		$time_start = microtime(TRUE);
		$time_end = $time_fetch = -1;
		$total_fetch = $total_parse = -1;

		$calendar = $this->input->get('calendar');
		if ($calendar === FALSE) {
			$this->extended_logs->message('ERROR',
					'Calendar events request with no calendar name');
			$err = 400;
		}

		$start = $this->input->get('start');
		$end = $this->input->get('end');

		if ($err == 0 && $start === FALSE) {
			// Something is wrong here
			$this->extended_logs->message('ERROR',
					'Calendar events request for ' . $calendar 
					.' with no start timestamp');
			$err = 400;
		} else if ($err == 0) {
			$start =
				$this->dates->datetime2idt(
						$this->dates->ts2datetime(
							$start,
							'UTC'));

			if ($end === FALSE) {
				$this->extended_logs->message('ERROR',
					'Calendar events request for ' . $calendar 
					.' with no end timestamp');
				$err = 400;
			} else {
				$end =
					$this->dates->datetime2idt(
							$this->dates->ts2datetime(
								$end,
								'UTC'));

				$returned_events = $this->caldav->fetch_events(
						$this->auth->get_user(),
						$this->auth->get_passwd(),
						$start, $end,
						$calendar);

				$time_fetch = microtime(TRUE);

				if ($returned_events === FALSE) {
					// Something went wrong
					$err = 500;
				}
			}
		}

		if ($err == 0) {
			$parsed =
				$this->icshelper->expand_and_parse_events($returned_events, 
						$start, $end, $calendar);

			$time_end = microtime(TRUE);

			$total_fetch = sprintf('%.4F', $time_fetch - $time_start);
			$total_parse = sprintf('%.4F', $time_end - $time_fetch);
			$total_time = sprintf('%.4F', $time_end - $time_start);


			$this->extended_logs->message('INTERNALS', 'Sending to client ' .
					count($parsed) . ' event(s) on calendar ' . $calendar 
					.' ['.$total_fetch.'/'.$total_parse.'/'.$total_time.']');

			$this->output->set_header("X-Fetch-Time: " . $total_fetch);
			$this->output->set_header("X-Parse-Time: " . $total_parse);
			$this->output->set_output(json_encode($parsed));
		} else {
			$this->output->set_status_header($err, 'Error');
		}
			
	}

	/**
	 * Deletes an event
	 * TODO: control whether we want to remove a single recurrence-id
	 * instead of the whole event
	 */
	function delete_event() {
		$calendar = $this->input->post('calendar');
		$uid = $this->input->post('uid');
		$href = $this->input->post('href');
		$etag = $this->input->post('etag');

		$response = array();

		if ($calendar === FALSE || $uid === FALSE || $href === FALSE ||
				$etag === FALSE || empty($calendar) || empty($uid) ||
				empty($href) || empty($calendar) || empty($etag)) {
			$this->extended_logs->message('ERROR', 
					'Call to delete_event() with no calendar, uid, href or etag');
			$this->_throw_error($this->i18n->_('messages',
						'error_interfacefailure'));
		} else {
			$this->load->library('caldav');
			$res = $this->caldav->delete_resource(
					$this->auth->get_user(),
					$this->auth->get_passwd(),
					$href,
					$calendar,
					$etag);
			if ($res === TRUE) {
				$this->_throw_success();
			} else {
				// There was an error
				$msg = $this->i18n->_('messages', $res[0],
						$res[1]);
				$this->_throw_exception($msg);
			}
		}
	}

	/**
	 * Creates or modifies an existing event
	 * TODO: detect if we are defining a new recurrence-id
	 */
	function com_event() {
		// Important data to be filled later
		$etag = '';
		$href = '';
		$calendar = '';
		$resource = null;
		// Default new properties. To be cleaned
		// on Icshelper library
		$p = $this->input->post(null, TRUE); // XSS

		$this->load->library('form_validation');
		$this->form_validation
			->set_rules('calendar', $this->i18n->_('labels', 'calendar'), 'required');
		$this->form_validation
			->set_rules('summary', $this->i18n->_('labels', 'summary'), 'required');
		$this->form_validation
			->set_rules('start_date', $this->i18n->_('labels', 'startdate'),
					'required|callback__valid_date');
		$this->form_validation
			->set_rules('end_date', $this->i18n->_('labels', 'enddate'),
					'required|callback__valid_date');
		$this->form_validation
			->set_rules('recurrence_count', $this->i18n->_('labels',
						'repeatcount'),
					'callback__empty_or_natural_no_zero');
		$this->form_validation
			->set_rules('recurrence_until', $this->i18n->_('labels',
						'repeatuntil'),
					'callback__empty_or_valid_date');

		if ($this->form_validation->run() === FALSE) {
			$this->_throw_exception(validation_errors());
		}

		$this->load->library('caldav');

		// DateTime objects
		$start = null;
		$end = null;

		$tz = isset($p['timezone']) ? $p['timezone'] : null;


		// Additional validations

		// 1. All day? If all day, require start_time, end_date and end_time
		// If not, generate our own values
		if (isset($p['allday']) && $p['allday'] == 'true') {
			// Start and end days, 00:00
			$start = $this->dates->frontend2datetime($p['start_date'] 
					. ' ' . date($this->time_format, mktime(0,0)), 'UTC');
			$end = $this->dates->frontend2datetime($p['end_date'] 
					. ' ' . date($this->time_format, mktime(0, 0)), 'UTC');
			// Add 1 day (iCalendar needs this)
			$end->add(new DateInterval('P1D'));
		} else {
			// Create new form validation rules
			$this->form_validation
				->set_rules('start_time', $this->i18n->_('labels',
							'starttime'),
						'required|callback__valid_time');
			$this->form_validation
				->set_rules('end_time', $this->i18n->_('labels',
							'endtime'),
						'required|callback__valid_time');

			if ($this->form_validation->run() === FALSE) {
				$this->_throw_exception(validation_errors());
			}

			// 2. Check if start date <= end date
			$start = $this->dates->frontend2datetime($p['start_date'] 
					. ' ' .  $p['start_time'], $tz);
			$end = $this->dates->frontend2datetime($p['end_date'] 
					. ' ' .  $p['end_time'], $tz);
			if ($end->getTimestamp() < $start->getTimestamp()) {
				$this->_throw_exception($this->i18n->_('messages',
							'error_startgreaterend'));
			}
		}

		$p['dtstart'] = $start;
		$p['dtend'] = $end;

		// Recurrency checks
		unset($p['rrule']);

		if (isset($p['recurrence_type'])) {
			if ($p['recurrence_type'] != 'none') {
				if (isset($p['recurrence_until']) &&
						!empty($p['recurrence_until'])) {
					$p['recurrence_until'] .= date($this->time_format,
							mktime(0, 0)); // Tricky
				}

				$rrule = $this->recurrency->build($p, $rrule_err);
				if (FALSE === $rrule) {
					// Couldn't build rrule
					$this->extended_logs->message('ERROR', 
							'Error building RRULE ('
								. $rrule_err .')');
					$this->_throw_exception($this->i18n->_('messages',
							'error_bogusrepeatrule') . ': ' . $rrule_err);
				}
			} else {
				// Deleted RRULE
				// TODO in the future, consider recurrence-id and so
				$rrule = '';
			}

			$p['rrule'] = $rrule;
		}


			
		// Is this a new event or a modification?

		// Valid destination calendar? 
		if (!$this->caldav->is_valid_calendar(
				$this->auth->get_user(),
				$this->auth->get_passwd(),
				$p['calendar'])) {
			$this->_throw_exception(
					$this->i18n->_('messages', 'error_calendarnotfound', 
						array('%calendar' => $p['calendar'])));
		} else {
			$calendar = $p['calendar'];
		}

		if (!isset($p['modification'])) {
			// New event (resource)
			$new_uid = $this->icshelper->new_resource($p,
					$resource, $this->tz);
			$href = $new_uid . '.ics';
			$etag = '*';
		} else {
			// Load existing resource

			// Valid original calendar?
			if (!isset($p['original_calendar'])) {
				$this->_throw_exception($this->i18n->_('messages',
							'error_interfacefailure'));
			} else {
				$original_calendar = $p['original_calendar'];
			}

			if (!$this->caldav->is_valid_calendar(
					$this->auth->get_user(),
					$this->auth->get_passwd(),
					$original_calendar)) {
				$this->_throw_exception(
					$this->i18n->_('messages', 'error_calendarnotfound', 
						array('%calendar' => $original_calendar)));
			}

			$uid = $p['uid'];
			$href = $p['href'];
			$etag = $p['etag'];

			$res = $this->caldav->fetch_resource_by_uid(
					$this->auth->get_user(),
					$this->auth->get_passwd(),
					$uid,
					$original_calendar);

			if (is_null($res)) {
				$this->_throw_error(
						$this->i18n->_('messages', 'error_eventnotfound'));
			}

			if ($etag != $res['etag']) {
				$this->_throw_error(
						$this->i18n->_('messages', 'error_eventchanged'));
			}


			$resource = $this->icshelper->parse_icalendar($res['data']);
			$timezones = $this->icshelper->get_timezones($resource);
			$vevent = null;
			// TODO: recurrence-id?
			$modify_pos =
				$this->icshelper->find_component_position($resource,
					'VEVENT', array(), $vevent);
			if (is_null($vevent)) {
				$this->_throw_error(
						$this->i18n->_('messages', 'error_eventnofound'));
			}

			$tz = $this->icshelper->detect_tz($vevent, $timezones);

			// Change every property
			$force_new_value = 
				(isset($p['allday']) && $p['allday'] == 'true') ? 
				'DATE' : 'DATE-TIME';

			$vevent = $this->icshelper->make_start($vevent, $tz,
					$start, null,
					$force_new_value);
			$vevent = $this->icshelper->make_end($vevent, $tz,
					$end, null,
					$force_new_value);

			$properties = array(
					'summary' => $p['summary'],
					'location' => $p['location'],
					'description' => $p['description'],
					);

			// Only change RRULE when we are able to
			if (isset($p['rrule'])) {
				$properties['rrule'] = $p['rrule'];
			}

			// CLASS and TRANSP
			if (isset($p['class'])) {
				$properties['class'] = $p['class'];
			}

			if (isset($p['transp'])) {
				$properties['transp'] = strtoupper($p['transp']);
			}

			$vevent = $this->icshelper->change_properties($vevent,
					$properties);

			$vevent = $this->icshelper->set_last_modified($vevent);
			$resource = $this->icshelper->replace_component($resource,
					'vevent', $modify_pos, $vevent);
			if ($resource === FALSE) {
				$this->_throw_error(
						$this->i18n->_('messages', 'error_internalgen'));
			}

			// Moving event between calendars
			if ($original_calendar != $calendar) {
				$res = $this->caldav->delete_resource(
						$this->auth->get_user(),
						$this->auth->get_passwd(),
						$href,
						$original_calendar,
						$etag);
				if ($res === TRUE) {
					$this->extended_logs->message('INTERNALS', 
							'Deleted event with uid=' . $uid 
							.' from calendar ' .  $original_calendar);
				} else {
					// There was an error
					$this->extended_logs->message('INTERNALS',
							'Error deleting event with uid=' . $uid
							.' from calendar ' . $original_calendar . ': '
							. $res);
					$this->_throw_exception($res);
				}

				// Generate new resource uid
				$etag = '*';
			}
		}

		// PUT on server
		$new_etag = $this->caldav->put_resource(
				$this->auth->get_user(),
				$this->auth->get_passwd(),
				$href,
				$calendar,
				$resource,
				$etag);

		if (FALSE === $new_etag) {
			$code = $this->caldav->get_last_response();
			switch ($code[0]) {
				case '412':
					// TODO new events + already used UIDs!
					if (isset($p['modification'])) {
						$this->_throw_exception(
							$this->i18n->_('messages', 'error_eventchanged'));
					} else {
						// Already used UID on new event. What a bad luck!
						// TODO propose a solution
						$this->_throw_error('Bad luck'
								.' Repeated UID');
					}
					break;
				default:
					$this->_throw_error( $this->i18n->_('messages',
								'error_unknownhttpcode',
								array('%res' =>  $code[0])));
					break;
			}
		} else {
			// Return a list of affected calendars (original_calendar, new
			// calendar)
			$affected_calendars = array($calendar);
			if (isset($original_calendar) && $original_calendar !=
					$calendar) {
				$affected_calendars[] = $original_calendar;
			}

			$this->_throw_success($affected_calendars);
		}
		
	}


	/**
	 * Resizing of an event
	 */
	function resize_or_drag_event() {
		$uid = $this->input->post('uid');
		$calendar = $this->input->post('calendar');
		$etag = $this->input->post('etag');
		$dayDelta = $this->input->post('dayDelta');
		$minuteDelta = $this->input->post('minuteDelta');
		$allday = $this->input->post('allday');
		$was_allday = $this->input->post('was_allday');
		$view = $this->input->post('view');
		$type = $this->input->post('type');

		if ($uid === FALSE || $calendar === FALSE ||
				$etag === FALSE || $dayDelta === FALSE || 
				$minuteDelta === FALSE || 
				$view === FALSE || $allday === FALSE ||
				$type === FALSE || $was_allday === FALSE) {
			$this->_throw_error($this->i18n->_('messages',
						'error_interfacefailure'));
		}

		// Generate a duration string
		$pattern = '/^(-)?([0-9]+)$/';
		if ($view == 'month') {
			$dur_string = preg_replace($pattern, '\1P\2D', $dayDelta);
		} else {
			// Going the easy way O:) 1D = 1440M
			$val = intval($minuteDelta) + intval($dayDelta)*1440;
			$minuteDelta = strval($val);
			$dur_string = preg_replace($pattern, '\1PT\2M', $minuteDelta);
		}

		// Load resource
		$resource = $this->caldav->fetch_resource_by_uid(
				$this->auth->get_user(),
				$this->auth->get_passwd(),
				$uid,
				$calendar);


		if (is_null($resource)) {
			$this->_throw_error(
						$this->i18n->_('messages', 'error_eventnotfound'));
		}

		if ($etag != $resource['etag']) {
			$this->_throw_error(
						$this->i18n->_('messages', 'error_eventchanged'));
		}

		// We're prepared to modify the event
		$href = $resource['href'];
		$ical = $this->icshelper->parse_icalendar($resource['data']);
		$timezones = $this->icshelper->get_timezones($ical);
		$vevent = null;
		// TODO: recurrence-id?
		$modify_pos = $this->icshelper->find_component_position($ical,
				'VEVENT', array(), $vevent);

		if (is_null($vevent)) {
			$this->_throw_error(
						$this->i18n->_('messages', 'error_eventnotfound'));
		}

		$tz = $this->icshelper->detect_tz($vevent, $timezones);

		/*
		log_message('INTERNALS', 'PRE: ['.$tz.'] ' 
				. $vevent->createComponent($x));
				*/

		// Distinguish between these two options
		if ($type == 'drag') {
			// 4 Posibilities
			if ($was_allday == 'true') {
				if ($allday == 'true') {
					// From all day to all day
					$tz = 'UTC';
					$new_vevent = $this->icshelper->make_start($vevent,
							$tz, null, $dur_string, 'DATE');
					$new_vevent = $this->icshelper->make_end($new_vevent,
							$tz, null, $dur_string, 'DATE');
				} else {
					// From all day to normal event
					// Use default timezone
					$tz = $this->tz;

					// Add VTIMEZONE
					$this->icshelper->add_vtimezone($ical, $tz, $timezones);

					// Set start date using default timezone instead of UTC
					$start = $this->icshelper->extract_date($vevent,
							'DTSTART', $tz);
					$start_obj = $start['result'];
					$start_obj->add($this->dates->duration2di($dur_string));
					$new_vevent = $this->icshelper->make_start($vevent,
							$tz, $start_obj, null, 'DATE-TIME',
							$tz);
					$new_vevent = $this->icshelper->make_end($new_vevent,
							$tz, $start_obj, 'PT1H', 'DATE-TIME', 
							$tz);
				}
			} else {
				// was_allday = false
				$force = ($allday == 'true' ? 'DATE' : null);
				$new_vevent = $this->icshelper->make_start($vevent, $tz,
						null, $dur_string, $force);
				if ($allday == 'true') {
					$new_start = $this->icshelper->extract_date($new_vevent,
							'DTSTART', $tz);
					$new_vevent = $this->icshelper->make_end($new_vevent,
							$tz, $new_start['result'], 'P1D', $force);
				} else {
					$new_vevent = $this->icshelper->make_end($new_vevent,
							$tz, null, $dur_string, $force);
				}
			}
		} else {
			$new_vevent = $this->icshelper->make_end($vevent,
					$tz, null, $dur_string);

			// Check if DTSTART == DTEND
			$new_dtstart = $this->icshelper->extract_date($new_vevent,
					'DTSTART', $tz);
			$new_dtend = $this->icshelper->extract_date($new_vevent,
					'DTEND', $tz);
			if ($new_dtstart['result'] == $new_dtend['result']) {
				// Avoid this
				$new_vevent = $this->icshelper->make_end($vevent,
						$tz, null, ($new_dtend['value'] == 'DATE' ? 'P1D' :
							'PT60M'));
			}


		}

		// Apply LAST-MODIFIED update
		$new_vevent = $this->icshelper->set_last_modified($new_vevent);

		/*
		log_message('INTERNALS', 'POS: ' .
				$new_vevent->createComponent($x));
				*/


		$ical = $this->icshelper->replace_component($ical, 'vevent',
				$modify_pos, $new_vevent);
		if ($ical === FALSE) {
			$this->_throw_error($this->i18n->_('messages',
						'error_internalgen'));
		}

		// PUT on server
		$new_etag = $this->caldav->put_resource(
				$this->auth->get_user(),
				$this->auth->get_passwd(),
				$href,
				$calendar,
				$ical,
				$etag);


		if (FALSE === $new_etag) {
			$code = $this->caldav->get_last_response();
			switch ($code[0]) {
				case '412':
					$this->_throw_exception(
							$this->i18n->_('messages', 'error_eventchanged'));
					break;
				default:
					$this->_throw_error( $this->i18n->_('messages',
								'error_unknownhttpcode',
								array('%res' =>  $code[0])));
					break;
			}
		} else {
			// Send new information about this event

			$info = $this->icshelper->parse_vevent_fullcalendar(
					$new_vevent, $href, $new_etag, $calendar, $tz);
			$this->_throw_success($info);
		}
	}


	/**
	 * Retrieve a list of calendars (owned by current user or shared by
	 * other users with the current one)
	 */
	function calendar_list() {
		// TODO order
		$own_calendars = $this->caldav->get_own_calendars(
				$this->auth->get_user(),
				$this->auth->get_passwd()
				);
		$arr_calendars = $own_calendars;

		// Look for shared calendars
		if ($this->config->item('enable_calendar_sharing')) {
			$tmp_shared_calendars = $this->shared_calendars->get_shared_with(
					$this->auth->get_user());

			if (is_array($tmp_shared_calendars) && count($tmp_shared_calendars) > 0) {
				$shared_calendars = $this->caldav->get_shared_calendars_info(
						$this->auth->get_user(),
						$this->auth->get_passwd(),
						$tmp_shared_calendars);
				if ($shared_calendars === FALSE) {
					$this->extended_logs->message('ERROR', 
							'Error reading shared calendars');
				} else {
					$arr_calendars = array_merge($arr_calendars,
							$shared_calendars);
				}
			}
		}

		// Save calendars into session (avoid multiple CalDAV queries when
		// editing/adding events)
		$this->session->set_userdata('available_calendars', $arr_calendars);

		$this->output->set_output(json_encode($arr_calendars));
	}

	/**
	 * Creates a calendar
	 */
	function create_calendar() {
		$displayname = $this->input->post('displayname', TRUE);
		$calendar_color = $this->input->post('calendar_color', TRUE);

		// Display name
		if (empty($displayname)) {
			$this->_throw_exception($this->i18n->_('messages',
						'error_calname_missing'));
		}

		// Default color
		if (empty($calendar_color)) {
			$calendar_color = '#' . $this->calendar_color[0];
		}

		// Get current own calendars
		$current_calendars = $this->caldav->get_own_calendars(
				$this->auth->get_user(),
				$this->auth->get_passwd()
				);

		// Generate internal calendar name
		do {
			$calendar = $this->icshelper->generate_guid();
		} while (isset($current_calendars[$calendar]));

		// Add transparency to color
		$calendar_color = $this->caldav->_rgb2rgba($calendar_color);

		// Calendar properties
		$props = array(
				'displayname' => $displayname,
				'color' => $calendar_color,
				);


		$res = $this->caldav->mkcalendar(
				$this->auth->get_user(),
				$this->auth->get_passwd(),
				$calendar,
				$props);

		if ($res !== TRUE) {
			$this->_throw_error($this->i18n->_('messages', $res[0], $res[1]));
		} else {
			$this->_throw_success();
		}
	}


	/**
	 * Deletes a calendar
	 */
	function delete_calendar() {
		$calendar = $this->input->post('calendar');
		if ($calendar === FALSE) {
			$this->extended_logs->message('ERROR', 
					'Call to delete_calendar() without calendar');
			$this->_throw_error($this->i18n->_('messages',
						'error_interfacefailure'));
		}

		// Get current own calendars and check if this one exists
		$current_calendars = $this->caldav->get_own_calendars(
				$this->auth->get_user(),
				$this->auth->get_passwd()
				);

		if (!isset($current_calendars[$calendar])) {
			$this->extended_logs->message('INTERNALS', 
					'Call to delete_calendar() with non-existent calendar ('
						.$calendar.')');
			$this->_throw_exception(
				$this->i18n->_('messages', 'error_calendarnotfound', 
					array('%calendar' => $p['calendar'])));
		}

		// Delete calendar shares (if any), even if calendar sharing is not
		// enabled
		$shares =
			$this->shared_calendars->get_shared_from($this->auth->get_user());

		if (isset($shares[$calendar])) {
			$this_calendar_shares = array_values($shares[$calendar]);
			foreach ($this_calendar_shares as $k => $data) {
				$this->shared_calendars->remove($data['sid']);
			}
		}

		$replace_pattern = '/^' . $this->auth->get_user() . ':/';
		$internal_calendar = preg_replace($replace_pattern, '', $calendar);


		// Proceed to remove calendar from CalDAV server
		$res = $this->caldav->delete_resource(
			$this->auth->get_user(),
			$this->auth->get_passwd(),
			'',
			$internal_calendar,
			null);

		if ($res === TRUE) {
			$this->_throw_success();
		} else {
			// There was an error
			$this->_throw_exception($this->i18n->_('messages', $res[0],
						$res[1]));
		}
	}

	/**
	 * Modifies a calendar
	 */
	function modify_calendar() {
		$is_sharing_enabled =
			$this->config->item('enable_calendar_sharing');
		$calendar = $this->input->post('calendar');
		$displayname = $this->input->post('displayname');
		$calendar_color = $this->input->post('calendar_color');

		$is_shared_calendar = $this->input->post('is_shared_calendar');

		// If calendar is from another user, the following two variables
		// contain the share id and user which shared it respectively
		$sid = $this->input->post('sid');
		$user_from = $this->input->post('user_from');

		// In case this calendar is owned by current user, this will contain
		// a list of users he/she wants to share the calendar with
		$share_with = $this->input->post('share_with');

		// When modifying your own calendar, these share ids will help
		// calculate needed database updates
		$orig_sids = $this->input->post('orig_sids');

		if ($calendar === FALSE || $displayname === FALSE || $calendar_color ===
				FALSE || ($is_sharing_enabled && $is_shared_calendar === FALSE)) {
			$this->extended_logs->message('ERROR', 
					'Call to modify_calendar() with incomplete parameters');
			$this->_throw_error($this->i18n->_('messages',
						'error_interfacefailure'));
		}

		// Calculate boolean value for is_shared_calendar
		$is_shared_calendar = ($is_shared_calendar === FALSE ?
				FALSE :
				($is_shared_calendar == 'true'));

		if ($is_sharing_enabled && $is_shared_calendar && ($sid === FALSE || $user_from === FALSE)) {
			$this->extended_logs->message('ERROR', 
					'Call to modify_calendar() with shared calendar and incomplete parameters');
			$this->_throw_error($this->i18n->_('messages',
						'error_interfacefailure'));
		}

		// Check if calendar is valid
		if (!$this->caldav->is_valid_calendar(
					$this->auth->get_user(),
					$this->auth->get_passwd(),
					$calendar)) {
			$this->extended_logs->message('INTERNALS', 
					'Call to modify_calendar() with non-existent calendar '
					.' or with access forbidden ('
						.$calendar.')');

			$this->_throw_exception(
				$this->i18n->_('messages', 'error_calendarnotfound', 
					array('%calendar' => $calendar)));
		}


		// Add transparency to color
		$calendar_color = $this->caldav->_rgb2rgba($calendar_color);

		// Calendar properties
		$props = array(
				'displayname' => $displayname,
				'color' => $calendar_color,
				);


		// Proceed to modify calendar
		if (!$is_shared_calendar) {
			$replace_pattern = '/^' . $this->auth->get_user() . ':/';
			$internal_calendar = preg_replace($replace_pattern, '', $calendar);

			$res = $this->caldav->proppatch(
				$this->auth->get_user(),
				$this->auth->get_passwd(),
				$internal_calendar,
				$props);
		} else if ($is_sharing_enabled) {
			// If this a shared calendar, store settings locally
			$success = $this->shared_calendars->store($sid,
					$user_from,
					$calendar,
					$this->auth->get_user(),
					$props);
			if ($success === FALSE) {
				$res = $this->i18n->_('messages', 'error_internal');
			} else {
				$res = TRUE;
			}
		} else {
			// Tried to modify a shared calendar when sharing is disabled
			$this->extended_logs->message('ERROR',
					'Tried to modify the shared calendar ' . $calendar
					.' when calendar sharing is disabled');
			$res = $this->i18n->_('messages', 'error_interfacefailure');
		}

		// Set ACLs
		if ($is_sharing_enabled && $res === TRUE && !$is_shared_calendar) {
			$set_shares = array();

			if (!is_array($share_with)) {
				$share_with = array();
			} else {
				foreach ($share_with as $share) {
					if (!isset($share['username']) ||
							!isset($share['write_access'])) {
						$this->extended_logs->messages('ERROR', 
								'Ignoring incomplete share row attributes'
								.' on calendar modification: '
								. serialize($share));
					} else {
						$set_shares[] = $share;
					}
				}
			}

			$res = $this->caldav->setacl(
					$this->auth->get_user(),
					$this->auth->get_passwd(),
					$internal_calendar,
					$set_shares);

			// Update shares on database
			if ($res === TRUE) {
				$orig_sids = (is_array($orig_sids) ? $orig_sids : array());

				$updated_sids = array();

				foreach ($set_shares as $share) {
					$this_sid = isset($share['sid']) ?
								$share['sid'] : null;

					$this->shared_calendars->store(
							$this_sid,
							$this->auth->get_user(),
							$internal_calendar,
							$share['username'],
							null, 					// Preserve options
							($share['write_access'] == 'rw' ? '1' : '0'));

					if (!is_null($this_sid)) {
						$updated_sids[$this_sid] = true;
					}
				}

				// Removed users
				foreach ($orig_sids as $sid) {
					if (!isset($updated_sids[$sid])) {
						$this->shared_calendars->remove($sid);
					}
				}
			}
		}

		if ($res === TRUE) {
			$this->_throw_success();
		} else {
			// There was an error
			$this->_throw_exception($this->i18n->_('messages', $res[0],
						$res[1]));
		}
	}



	/**
	 * Input validators
	 */

	// Validate date format
	function _valid_date($d) {
		$obj = $this->dates->frontend2datetime($d .' ' .
				date($this->time_format));
		if (FALSE === $obj) {
			$this->form_validation->set_message('_valid_date',
					$this->i18n->_('messages', 'error_invaliddate'));
			return FALSE;
		} else {
			return TRUE;
		}
	}

	// Validate date format (or empty string)
	function _empty_or_valid_date($d) {
		return empty($d) || $this->_valid_date($d);
	}

	// Validate empty or > 0
	function _empty_or_natural_no_zero($n) {
		return empty($n) || intval($n) > 0;
	}

	// Validate time format
	function _valid_time($t) {
		$obj = $this->dates->frontend2datetime(date($this->date_format) .' '. $t);
		if (FALSE === $obj) {
			$this->form_validation->set_message('_valid_time',
					$this->i18n->_('messages', 'error_invalidtime'));
			return FALSE;
		} else {
			return TRUE;
		}
	}


	/**
	 * Throws an exception message
	 */
	function _throw_exception($message) {
		$this->output->set_output(json_encode(array(
						'result' => 'EXCEPTION',
						'message' => $message)));
		$this->output->_display();
		die();
	}

	/**
	 * Throws an error message
	 */
	function _throw_error($message) {
		$this->output->set_output(json_encode(array(
						'result' => 'ERROR',
						'message' => $message)));
		$this->output->_display();
		die();
	}

	/**
	 * Throws a success message
	 */
	function _throw_success($message = '') {
		$this->output->set_output(json_encode(array(
						'result' => 'SUCCESS',
						'message' => $message)));
		$this->output->_display();
		die();
	}



}
