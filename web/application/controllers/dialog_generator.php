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

class Dialog_generator extends CI_Controller {

	// Formats
	private $time_format;
	private $date_format;

	// Timezone
	private $tz;

	function __construct() {
		parent::__construct();

		$this->output->set_content_type('text/html');

		if (!$this->auth->is_authenticated()) {
			$this->extended_logs->message('INTERNALS', 
					'Anonymous access attempt to '
					. uri_string());
			$expire = $this->load->view('js_code/session_expired', '', true);
			echo $expire;
			exit;
		} else {
			$this->load->helper('form');
			
			// Load formats
			$this->date_format = $this->dates->date_format_string('date');
			$this->time_format = $this->dates->time_format_string('date');

			// Timezone
			$this->tz = $this->config->item('default_timezone');
		}
	}

	function index() {
	}

	/**
	 * Generates a view to remove an event
	 */
	function delete_event() {
		$this->load->view('dialogs/delete_event');
	}


	/**
	 * Generates a view to add an event
	 */
	function create_event() {

		// Start/end date passed?
		$start = $this->input->post('start');
		$end = $this->input->post('end');

		if (FALSE === $start) {
			$start = time();
		}

		// All day?
		$allday = $this->input->post('allday');
		if ($allday === FALSE || $allday == 'false') {
			$allday = FALSE;
		} else {
			$allday = TRUE;
		}

		// View
		$view = $this->input->post('view');

		$dstart = null;
		$dend = null;

		// Base DateTime start
		$dstart = $this->dates->fullcalendar2datetime($start, 'UTC');

		// TODO make default duration configurable

		if ($view == 'month') {
			// Calculate times
			$now = $this->dates->approx_by_factor(null, $this->tz);
			$dstart->setTime($now->format('H'), $now->format('i'));
			if ($end === FALSE || $start == $end) {
				$dend = clone $dstart;
				$dend->add(new DateInterval('PT60M'));
			} else {
				$dend = $this->dates->
					fullcalendar2datetime($end, 'UTC');
				$dend->setTime($dstart->format('H'), $dstart->format('i'));
			}
		} elseif ($allday === FALSE) {
			if ($end === FALSE || $start == $end) {
				$dend = clone $dstart;
				$dend->add(new DateInterval('PT60M')); // 1h
			} else {
				$dend = $this->dates->
					fullcalendar2datetime($end, 'UTC');
			}
		} else {
			$dstart->setTime(0, 0);
			$dend = clone $dstart;
			$dend->add(new DateInterval('P1D'));
		}

		// Calendars
		$tmp_cals= $this->session->userdata('available_calendars');
		$calendars = array();
		if ($tmp_cals === FALSE) {
			$this->extended_logs->message('ERROR',
					'Call to create_or_modify_event() with no calendars stored in session');
		} else {
			foreach ($tmp_cals as $id => $data) {
				$calendars[$id] = $data['displayname'];
			}
		}

		// Currently selected calendar (or calendar on which 
		// this event is placed on)
		$calendar = $this->input->post('current_calendar');
		if ($calendar === FALSE) {
			// Use first one if no calendar is selected
			$calendar = array_shift(array_keys($calendars));
		}

		$data = array(
				'start_date' => $dstart->format($this->date_format),
				'start_time' => $dstart->format($this->time_format),
				'end_date' => $dend->format($this->date_format),
				'end_time' => $dend->format($this->time_format),
				'allday' => $allday,
				'calendars' => $calendars,
				'calendar' => $calendar,
				);
		$this->load->view('dialogs/create_or_modify_event', $data);
	}

	/**
	 * Creates a dialog to edit an event
	 */
	function edit_event() {
		$uid = $this->input->post('uid');
		$calendar = $this->input->post('calendar');
		$href = $this->input->post('href');
		$etag = $this->input->post('etag');
		$start = $this->input->post('start');
		$end = $this->input->post('end');
		$allday = $this->input->post('allday');
		$summary = $this->input->post('summary');
		$location = $this->input->post('location');
		$description = $this->input->post('description');
		// TODO: do something with this
		$rrule = $this->input->post('rrule');
		$rrule_serialized = $this->input->post('rrule_serialized');
		$rrule_explained = $this->input->post('rrule_explained');
		$class = $this->input->post('icalendar_class');
		$transp = $this->input->post('transp');
		$recurrence_id = $this->input->post('recurrence_id');
		$orig_start = $this->input->post('orig_start');
		$orig_end = $this->input->post('orig_end');

		// Required fields
		if ($uid === FALSE || $calendar === FALSE || $href === FALSE
				|| $etag === FALSE || $start === FALSE 
				|| $end === FALSE || $allday === FALSE) {
			$this->_throw_error('com_event', 
					$this->i18n->_('messages', 'error_oops'),
					$this->i18n->_('messages', 'error_interfacefailure'));
		} elseif ($recurrence_id != 'undefined') {
			$this->_throw_error('com_event',
					$this->i18n->_('messages', 'error_oops'),
					$this->i18n->_('messages', 'not_implemented',
						array(
							'%feature' => $this->i18n->_('labels',
								'repetitionexceptions'))));
		} else {
			// Calendars
			$tmp_cals= $this->session->userdata('available_calendars');
			$calendars = array();
			if ($tmp_cals === FALSE) {
				$this->extended_logs->message('ERROR',
						'Call to create_or_modify_event() with no calendars stored in session');
			} else {
				foreach ($tmp_cals as $id => $data) {
					$calendars[$id] = $data['displayname'];
				}
			}

			$data = array(
					'modification' => TRUE,
					'uid' => $uid,
					'calendar' => $calendar,
					'href' => $href,
					'etag' => $etag,
					'summary' => $summary,
					'location' => $location,
					'description' => $description,
					'rrule' => $rrule,
					'calendars' => $calendars,
					'calendar' => $calendar,
					);

			if ($class !== FALSE) {
				$data['class'] = $class;
			}

			if ($transp !== FALSE) {
				$data['transp'] = strtoupper($transp);
			}


			// RRULE time, neccesary for start and end dates
			if ($rrule != 'undefined') {
				// TODO recurrence-ids should change this
				
				// Is this event the "original" one? If not, use orig_*
				// passed values 
				if ($orig_start !== FALSE && $orig_start != 'undefined' &&
						$orig_end !== FALSE && $orig_end != 'undefined') {
					// Change initial dates with these
					$start = $orig_start;
					$end = $orig_end;
				}

				// Parseable ?
				$res = ($rrule_explained != 'undefined');
				if ($res === FALSE) {
					$data['unparseable_rrule'] = TRUE;
					$data['rrule_raw'] = $rrule;
				} else {
					if ($rrule_serialized == 'undefined') {
						// No serialized value?
						$this->extended_logs->message('ERROR',
								'rrule_serialized undefined while editing'
								. $uid . ' at calendar ' . $calendar);
						$this->_throw_error('com_event',
								$this->i18n->_('messages', 'error_oops'),
								$this->i18n->_('messages',
									'error_interfacefailure'));
						return;
					}

					$rrule_serialized = @base64_decode($rrule_serialized);
					if ($rrule_serialized == FALSE) {
						$this->extended_logs->message('ERROR',
								'rrule_serialized b64 failed while editing'
								. $uid . ' at calendar ' . $calendar);
						$this->_throw_error('com_event',
								$this->i18n->_('messages', 'error_oops'),
								$this->i18n->_('messages',
									'error_interfacefailure'));
						return;
					}

					$rrule_arr = @unserialize($rrule_serialized);
					if ($rrule_arr === FALSE) {
						$this->extended_logs->message('ERROR',
								'rrule unserialize failed while editing'
								. $uid . ' at calendar ' . $calendar);
						$this->_throw_error('com_event',
								$this->i18n->_('messages', 'error_oops'),
								$this->i18n->_('messages',
									'error_interfacefailure'));
						return;
					}

					// UNTIL translation
					if (isset($rrule_arr['UNTIL'])) {
						// TODO timezone and configurable format
						$rrule_arr['UNTIL'] =
							$this->dates->idt2datetime($rrule_arr['UNTIL'],
									$this->tz)->format($this->date_format);
					}
					$data['recurrence'] = $rrule_arr;
				}

			}

			$start_obj = $this->dates->fullcalendar2datetime($start, 'UTC');
			$end_obj = $this->dates->fullcalendar2datetime($end, 'UTC');

			if ($allday == 'true') {
				$data['allday'] = TRUE;
				// Fullcalendar uses -1d on all day events
				$end_obj->add(new DateInterval('P1D'));
			}

			$data['start_date'] = $start_obj->format($this->date_format);
			$data['end_date'] = $end_obj->format($this->date_format);
			$data['start_time'] = $start_obj->format($this->time_format);
			$data['end_time'] = $end_obj->format($this->time_format);


			// Clean 'undefined' values
			$data = array_filter($data, array($this, '_not_undefined'));

			$this->load->view('dialogs/create_or_modify_event', $data);
		}
	}

	/**
	 * Calendar creation dialog
	 */
	function create_calendar() {
		$calendar_colors = $this->config->item('calendar_colors');

		$this->load->view('dialogs/create_calendar',
				array(
					'default_calendar_color' => '#' . $calendar_colors[0]
					));
	}

	/**
	 * Calendar modification dialog
	 */
	function modify_calendar() {
		$calendar = $this->input->post('calendar');
		$displayname = $this->input->post('displayname');
		$color = $this->input->post('color');
		$shared = $this->input->post('shared');
		$user_from = $this->input->post('user_from');
		$sid = $this->input->post('sid');
		$url = $this->input->post('url');

		if ($calendar === FALSE || $displayname === FALSE 
				|| $color === FALSE || $url === FALSE) {
			$this->_throw_error('modify_calendar_dialog', 
					$this->i18n->_('messages', 'error_oops'),
					$this->i18n->_('messages', 'error_interfacefailure'));
		} else {
			$data = array(
					'calendar' => $calendar,
					'displayname' => $displayname,
					'url' => $url,
					'color' => $color,
					);
			// Public URL
			if ($this->config->item('show_public_caldav_url') === TRUE) {
				$this->load->library('caldav', array('light' => TRUE));
				$data['public_url'] = $this->caldav->construct_public_url(
						$calendar);
			}

			// Sharings
			if ($shared !== FALSE && $shared == 'true') {
				if ($sid === FALSE || $user_from === FALSE) {
					$this->_throw_error('modify_calendar_dialog', 
						$this->i18n->_('messages', 'error_oops'),
						$this->i18n->_('messages',
							'error_interfacefailure'));

				} else {
					$data['shared'] = TRUE;
					$data['sid'] = $sid;
					$data['user_from'] = $user_from;
				}
			} else {
				// Users who can access this calendar
				$data['share_with'] =
					$this->shared_calendars->users_with_access_to($calendar,
							TRUE);
			}

			$this->load->view('dialogs/modify_calendar', $data);
		}
	}

	/**
	 * Delete calendar
	 */
	function delete_calendar() {
		$calendar = $this->input->post('calendar');
		$displayname = $this->input->post('displayname');

		if ($calendar === FALSE || $displayname === FALSE) {
			$this->_throw_error('delete_calendar_dialog', 
				$this->i18n->_('messages', 'error_oops'),
				$this->i18n->_('messages', 'error_interfacefailure'));
		} else {
			$data = array(
					'calendar' => $calendar,
					'displayname' => $displayname,
					);
			$this->load->view('dialogs/delete_calendar', $data);
		}
	}

	/**
	 * Creates an empty form
	 */
	function on_the_fly_form($random_id) {
		$this->load->view('dialogs/on_the_fly_form',
				array(
					'id' => $random_id,
				));
	}

	/**
	 * Callback for array_filter() to clean undefined values (only strings)
	 */
	function _not_undefined($a) {
		// strcmp vs ==: TRUE booleans match every string!
		return (!is_string($a) || (0 != strcmp($a, 'undefined')));
	}

	/*
	 * Close dialog and send error via JS
	 */
	function _throw_error($id, $title, $msg) {
		$this->load->view('js_code/close_dialog_and_show_error',
				array(
					'dialog' => $id,
					'title' => $title,
					'content' => $msg,
					));
	}

}
