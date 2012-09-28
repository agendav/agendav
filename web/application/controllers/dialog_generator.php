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

class Dialog_generator extends MY_Controller
{
    // Formats
    private $time_format;
    private $date_format;

    // Timezone
    private $tz;

    // UTC timezone (used several times)
    private $tz_utc;

    private $user;

    function __construct() {
        parent::__construct();

        $this->output->set_content_type('text/html');

        $this->user = $this->container['user'];

        if (!$this->user->isAuthenticated()) {
            $this->extended_logs->message('INTERNALS', 'Anonymous access attempt to ' . uri_string());
            $expire = $this->load->view('js_code/session_expired', '', true);
            echo $expire;
            exit;
        } else {
            $this->load->helper('form');

            $this->caldavoperations->setClient($this->container['client']);
            
            // Load formats
            $this->date_format = $this->dates->date_format_string('date');
            $this->time_format = $this->dates->time_format_string('date');

            // Timezone
            $this->tz = $this->timezonemanager->getTz( $this->config->item('default_timezone'));
            $this->tz_utc = $this->timezonemanager->getTz('UTC');
        }
    }

    function index() {
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
        $dstart = $this->dates->fullcalendar2datetime($start, $this->tz_utc);

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
                    fullcalendar2datetime($end, $this->tz_utc);
                $dend->setTime($dstart->format('H'), $dstart->format('i'));
            }
        } elseif ($allday === FALSE) {
            if ($end === FALSE || $start == $end) {
                $dend = clone $dstart;
                $dend->add(new DateInterval('PT60M')); // 1h
            } else {
                $dend = $this->dates->
                    fullcalendar2datetime($end, $this->tz_utc);
            }
        } else {
            $dstart->setTime(0, 0);
            if ($end === FALSE) {
                $dend = clone $dstart;
            } else {
                $dend = $this->dates->fullcalendar2datetime($end,
                        $this->tz_utc);
            }
        }

        // Calendars
        $calendars = array();
        foreach ($this->caldavoperations->getCalendars() as $id => $data) {
            if (!$data->shared || $data->write_access == '1') {
                $calendars[$id] = $data->displayname;
            }
        }

        // Currently selected calendar (or calendar on which 
        // this event is placed on)
        $calendar = $this->input->post('current_calendar');
        if ($calendar === FALSE) {
            // Use the calendar specified in preferences
            $prefs = $this->user->getPreferences();
            $calendar = $prefs->default_calendar;
            if ($calendar === FALSE) {
                $calendar = array_shift(array_keys($calendars));
            }
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
            $calendars = array();
            foreach ($this->caldavoperations->getCalendars() as $id => $data) {
                if (!$data->shared || $data->write_access == '1') {
                    $calendars[$id] = $data->displayname;
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

            $start_obj = $this->dates->fullcalendar2datetime($start,
                    $this->tz_utc);
            if ($end == 'undefined') {
                // Maybe same date and time. Clone start
                $end_obj = clone $start_obj;
            } else {
                $end_obj = $this->dates->fullcalendar2datetime($end,
                        $this->tz_utc);
            }

            if ($allday == 'true') {
                $data['allday'] = TRUE;
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
