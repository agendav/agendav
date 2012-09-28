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

use AgenDAV\Data\Reminder;

class Event extends MY_Controller
{

    private $time_format;
    private $date_format;

    private $tz;
    private $tz_utc;

    private $user;

    function __construct() {
        parent::__construct();

        $this->user = $this->container['user'];

        if (!$this->user->isAuthenticated()) {
            $this->extended_logs->message('INFO', 
                    'Anonymous access attempt to '
                    . uri_string());
            $this->output->set_status_header('401');
            $this->output->_display();
            die();
        }

        $this->caldavoperations->setClient($this->container['client']);

        $this->date_format = $this->dates->date_format_string('date');
        $this->time_format = $this->dates->time_format_string('date');

        $this->tz = $this->timezonemanager->getTz(
                $this->config->item('default_timezone'));
        $this->tz_utc = $this->timezonemanager->getTz('UTC');

        $this->output->set_content_type('application/json');
    }

    function index() {
    }

    function all() {
        $returned_events = array();
        $err = 0;

        // For benchmarking
        $time_start = microtime(true);
        $time_end = $time_fetch = -1;
        $total_fetch = $total_parse = -1;

        $calendar = $this->input->get('calendar');
        if ($calendar === false) {
            $this->extended_logs->message('ERROR', 'Calendar events request with no calendar name');
            $err = 400;
        }

        $start = $this->input->get('start');
        $end = $this->input->get('end');

        if ($err == 0 && $start === false) {
            // Something is wrong here
            $this->extended_logs->message(
                    'ERROR',
                    'Calendar events request for ' . $calendar .' with no start timestamp');
            $err = 400;
        } else if ($err == 0) {
            $start =
                $this->dates->datetime2idt($this->dates->ts2datetime($start, $this->tz_utc));

            if ($end === false) {
                $this->extended_logs->message(
                        'ERROR',
                        'Calendar events request for ' . $calendar .' with no end timestamp');
                $err = 400;
            } else {
                $end = $this->dates->datetime2idt($this->dates->ts2datetime($end, $this->tz_utc));

                $returned_events = $this->caldavoperations->fetchEvents($calendar, $start, $end);

                $time_fetch = microtime(true);
            }
        }

        if ($err == 0) {
            $parsed =
                $this->icshelper->expand_and_parse_events($returned_events, $start, $end, $calendar);

            $time_end = microtime(true);

            $total_fetch = sprintf('%.4F', $time_fetch - $time_start);
            $total_parse = sprintf('%.4F', $time_end - $time_fetch);
            $total_time = sprintf('%.4F', $time_end - $time_start);


            $this->extended_logs->message(
                    'INTERNALS',
                    'Sending to client ' .  count($parsed) . ' event(s) on calendar ' . $calendar 
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
    function delete() {
        $calendar = $this->input->post('calendar');
        $uid = $this->input->post('uid');
        $href = $this->input->post('href');
        $etag = $this->input->post('etag');

        $response = array();

        if ($calendar === false || $uid === false || $href === false ||
                $etag === false || empty($calendar) || empty($uid) ||
                empty($href) || empty($calendar) || empty($etag)) {
            $this->extended_logs->message('ERROR', 
                    'Call to delete_event() with no calendar, uid, href or etag');
            $this->_throw_error($this->i18n->_('messages',
                        'error_interfacefailure'));
        } else {
            $res = $this->caldavoperations->deleteResource(
                    $href,
                    $etag);
            if ($res === true) {
                $this->_throw_success();
            } else {
                // There was an error
                $params = array();
                switch ($res) {
                    case '404':
                        $usermsg = 'error_eventnotfound';
                        break;
                    case '412':
                        $usermsg = 'error_eventchanged';
                        break;
                    default:
                        $usermsg = 'error_unknownhttpcode';
                        $params['%res'] = $res;
                        break;
                }

                $msg = $this->i18n->_('messages', $usermsg, $params);
                $this->_throw_exception($msg);
            }
        }
    }

    /**
     * Creates or modifies an existing event
     * TODO: detect if we are defining a new recurrence-id
     */
    function modify() {
        // Important data to be filled later
        $etag = '';
        $href = '';
        $calendar = '';
        $resource = null;
        // Default new properties. To be cleaned
        // on Icshelper library
        $p = $this->input->post(null, true); // XSS

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

        if ($this->form_validation->run() === false) {
            $this->_throw_exception(validation_errors());
        }

        // DateTime objects
        $start = null;
        $end = null;

        $tz = isset($p['timezone']) ? 
            $this->timezonemanager->getTz($p['timezone']) : 
            $this->timezonemanager->getTz(
                    $this->config->item('default_timezone'));


        // Additional validations

        // 1. All day? If all day, require start_time, end_date and end_time
        // If not, generate our own values
        if (isset($p['allday']) && $p['allday'] == 'true') {
            // Start and end days, 00:00
            $start = $this->dates->frontend2datetime($p['start_date'] 
                    . ' ' . date($this->time_format, mktime(0,0)), 
                    $this->tz_utc);
            $end = $this->dates->frontend2datetime($p['end_date'] 
                    . ' ' . date($this->time_format, mktime(0, 0)), 
                    $this->tz_utc);
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

            if ($this->form_validation->run() === false) {
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

        // Recurrence checks
        unset($p['rrule']);

        if (isset($p['recurrence_type'])) {
            if ($p['recurrence_type'] != 'none') {
                if (isset($p['recurrence_until']) &&
                        !empty($p['recurrence_until'])) {
                    $p['recurrence_until'] .= date($this->time_format,
                            mktime(0, 0)); // Tricky
                }

                $rrule = $this->recurrence->build($p, $rrule_err);
                if (false === $rrule) {
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


        // Reminders
        $reminders = array();

        // Contains a list of old parseable (visible on UI) reminders.
        // Used to remove reminders that were deleted by user
        $visible_reminders = isset($p['visible_reminders']) ?
            $p['visible_reminders'] : array();

        if (isset($p['reminders']) && is_array($p['reminders'])) {
            $data_reminders = $p['reminders'];
            $num_reminders = count($data_reminders['is_absolute']);

            for($i=0;$i<$num_reminders;$i++) {
                $this_reminder = null;
                $data_reminders['is_absolute'][$i] =
                    ($data_reminders['is_absolute'][$i] == 'true' ? true :
                     false);

                if ($data_reminders['is_absolute'][$i]) {
                    $when =
                        $this->dates->frontend2datetime($data_reminders['tdate'][$i]
                                . ' ' . $data_reminders['ttime'][$i],
                                $this->tz);
                    $when->setTimezone($this->tz_utc);
                    $this_reminder = Reminder::createFrom($when);
                } else {
                    $when = array(
                            'before' => ($data_reminders['before'][$i] ==
                                'true'),
                            'relatedStart' =>
                            ($data_reminders['relatedStart'][$i] == 'true'),
                            );
                    $interval = $data_reminders['interval'][$i];
                    $when[$interval] = $data_reminders['qty'][$i];

                    $this_reminder = Reminder::createFrom($when);
                }

                if (!empty($data_reminders['order'][$i])) {
                    $this_reminder->order = $data_reminders['order'][$i];
                }

                log_message('INTERNALS', 'Adding reminder ' .  $this_reminder);
                $reminders[] = $this_reminder;
            }
        }

            
        // Is this a new event or a modification?

        // Valid destination calendar? 
        if (!$this->caldavoperations->isAccessible($p['calendar'])) {
            $this->_throw_exception(
                    $this->i18n->_('messages', 'error_calendarnotfound', array('%calendar' => $p['calendar']))
            );
        } else {
            $calendar = $p['calendar'];
        }

        if (!isset($p['modification'])) {
            // New event (resource)
            $new_uid = $this->icshelper->new_resource($p,
                    $resource, $this->tz, $reminders);
            $href = $new_uid . '.ics';
            $etag = '*';
        } else {
            // Load existing resource

            // Valid original calendar?
            if (!isset($p['original_calendar'])) {
                $this->_throw_exception($this->i18n->_('messages', 'error_interfacefailure'));
            } else {
                $original_calendar = $p['original_calendar'];
            }

            if (!$this->caldavoperations->isAccessible($original_calendar)) {
                $this->_throw_exception(
                    $this->i18n->_('messages', 'error_calendarnotfound', array('%calendar' => $original_calendar))
                );
            }

            $uid = $p['uid'];
            $href = $p['href'];
            $etag = $p['etag'];

            $res = $this->caldavoperations->fetchEntryByUid($original_calendar, $uid);

            if (count($res) == 0) {
                $this->_throw_error($this->i18n->_('messages', 'error_eventnotfound'));
            }

            if ($etag != $res['etag']) {
                $this->_throw_error($this->i18n->_('messages', 'error_eventchanged'));
            }


            $resource = $this->icshelper->parse_icalendar($res['data']);
            $timezones = $this->icshelper->get_timezones($resource);
            $vevent = null;
            // TODO: recurrence-id?
            $modify_pos = $this->icshelper->find_component_position($resource, 'VEVENT', array(), $vevent);
            if (is_null($vevent)) {
                $this->_throw_error( $this->i18n->_('messages', 'error_eventnofound'));
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

            $vevent = $this->icshelper->change_properties($vevent, $properties);

            // Add/change/remove reminders
            $vevent = $this->icshelper->set_valarms($vevent, $reminders, $visible_reminders);

            $vevent = $this->icshelper->set_last_modified($vevent);
            $resource = $this->icshelper->replace_component($resource, 'vevent', $modify_pos, $vevent);
            if ($resource === false) {
                $this->_throw_error($this->i18n->_('messages', 'error_internalgen'));
            }

            // Moving event between calendars
            if ($original_calendar != $calendar) {
                // We will need this etag later
                $original_etag = $etag;
                $etag = '*';
            }
        }

        // PUT on server
        $new_etag = $this->caldavoperations->putResource(
                $calendar . $href,
                $resource->createCalendar(),
                $etag);

        // Error
        if (is_array($new_etag)) {
            $code = current($new_etag);
            switch ($code) {
                case '412':
                    // TODO new events + already used UIDs!
                    if (isset($p['modification'])) {
                        $this->_throw_exception( $this->i18n->_('messages', 'error_eventchanged'));
                    } else {
                        // Already used UID on new event. What a bad luck!
                        // TODO propose a solution
                        $this->_throw_error('Bad luck' .' Repeated UID');
                    }
                    break;
                case '403':
                    $this->_throw_error($this->i18n->_('messages', 'error_denied'));
                    break;
                default:
                    $this->_throw_error($this->i18n->_('messages', 'error_unknownhttpcode', array('%res' => $code[0])));
                    break;
            }
        } else {
            // Remove original event
            if (isset($p['modification']) && $original_calendar != $calendar) {
                $res = $this->caldavoperations->deleteResource(
                        $original_calendar . $href,
                        $original_etag
                );
                if ($res !== true) {
                    // There was an error
                    $this->_throw_exception('');
                }
            }

            // Return a list of affected calendars (original_calendar, new
            // calendar)
            $affected_calendars = array($calendar);
            if (isset($original_calendar) && $original_calendar != $calendar) {
                $affected_calendars[] = $original_calendar;
            }

            $this->_throw_success($affected_calendars);
        }
    }


    /**
     * Resizing an event
     */
    function alter() {
        $uid = $this->input->post('uid');
        $calendar = $this->input->post('calendar');
        $etag = $this->input->post('etag');
        $dayDelta = $this->input->post('dayDelta');
        $minuteDelta = $this->input->post('minuteDelta');
        $allday = $this->input->post('allday');
        $was_allday = $this->input->post('was_allday');
        $view = $this->input->post('view');
        $type = $this->input->post('type');

        if ($uid === false || $calendar === false ||
                $etag === false || $dayDelta === false || 
                $minuteDelta === false || 
                $view === false || $allday === false ||
                $type === false || $was_allday === false) {
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
        $resource = $this->caldavoperations->fetchEntryByUid($calendar, $uid);

        if (count($resource) == 0) {
            $this->_throw_error( $this->i18n->_('messages', 'error_eventnotfound'));
        }

        if ($etag != $resource['etag']) {
            $this->_throw_error($this->i18n->_('messages', 'error_eventchanged'));
        }

        // We're prepared to modify the event
        $href = $resource['href'];
        $ical = $this->icshelper->parse_icalendar($resource['data']);
        $timezones = $this->icshelper->get_timezones($ical);
        $vevent = null;
        // TODO: recurrence-id?
        $modify_pos = $this->icshelper->find_component_position($ical, 'VEVENT', array(), $vevent);

        if ($vevent === null) {
            $this->_throw_error( $this->i18n->_('messages', 'error_eventnotfound'));
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
                    $tz = $this->tz_utc;
                    $new_vevent = $this->icshelper->make_start($vevent, $tz, null, $dur_string, 'DATE');
                    $new_vevent = $this->icshelper->make_end($new_vevent, $tz, null, $dur_string, 'DATE');
                } else {
                    // From all day to normal event
                    // Use default timezone
                    $tz = $this->tz;

                    // Add VTIMEZONE
                    $this->icshelper->add_vtimezone($ical, $tz->getName(), $timezones);

                    // Set start date using default timezone instead of UTC
                    $start = $this->icshelper->extract_date($vevent,
                            'DTSTART', $tz);
                    $start_obj = $start['result'];
                    $start_obj->add($this->dates->duration2di($dur_string));
                    $new_vevent = $this->icshelper->make_start(
                            $vevent,
                            $tz,
                            $start_obj,
                            null,
                            'DATE-TIME',
                            $tz->getName()
                    );
                    $new_vevent = $this->icshelper->make_end(
                            $new_vevent,
                            $tz,
                            $start_obj,
                            'PT1H',
                            'DATE-TIME',
                            $tz->getName()
                    );
                }
            } else {
                // was_allday = false
                $force = ($allday == 'true' ? 'DATE' : null);
                $new_vevent = $this->icshelper->make_start($vevent, $tz, null, $dur_string, $force);
                if ($allday == 'true') {
                    $new_start = $this->icshelper->extract_date($new_vevent, 'DTSTART', $tz);
                    $new_vevent = $this->icshelper->make_end($new_vevent, $tz, $new_start['result'], 'P1D', $force);
                } else {
                    $new_vevent = $this->icshelper->make_end($new_vevent, $tz, null, $dur_string, $force);
                }
            }
        } else {
            $new_vevent = $this->icshelper->make_end($vevent, $tz, null, $dur_string);

            // Check if DTSTART == DTEND
            $new_dtstart = $this->icshelper->extract_date($new_vevent, 'DTSTART', $tz);
            $new_dtend = $this->icshelper->extract_date($new_vevent, 'DTEND', $tz);
            if ($new_dtstart['result'] == $new_dtend['result']) {
                // Avoid this
                $new_vevent = $this->icshelper->make_end(
                        $vevent,
                        $tz,
                        null,
                        ($new_dtend['value'] == 'DATE' ? 'P1D' : 'PT60M')
                );
            }


        }

        // Apply LAST-MODIFIED update
        $new_vevent = $this->icshelper->set_last_modified($new_vevent);

        /*
        log_message('INTERNALS', 'POS: ' .
                $new_vevent->createComponent($x));
                */


        $ical = $this->icshelper->replace_component($ical, 'vevent', $modify_pos, $new_vevent);
        if ($ical === false) {
            $this->_throw_error($this->i18n->_('messages', 'error_internalgen'));
        }

        // PUT on server
        $new_etag = $this->caldavoperations->putResource(
                $calendar . $href,
                $ical->createCalendar(),
                $etag
        );


        // Error
        if (is_array($new_etag)) {
            $new_etag = current($new_etag);
            switch ($new_etag) {
                case '412':
                    $this->_throw_exception( $this->i18n->_('messages', 'error_eventchanged'));
                    break;
                default:
                    $this->_throw_error( $this->i18n->_('messages', 'error_unknownhttpcode', array('%res' => $new_etag)));
                    break;
            }
        } else {
            // Send new information about this event
            $info = $this->icshelper->parse_vevent_fullcalendar(
                    $new_vevent,
                    $href,
                    $new_etag,
                    $calendar,
                    $tz,
                    $timezones
            );
            $this->_throw_success($info);
        }
    }

    /**
     * Input validators
     */

    // Validate date format
    function _valid_date($d) {
        $obj = $this->dates->frontend2datetime($d .' ' .
                date($this->time_format));
        if (false === $obj) {
            $this->form_validation->set_message('_valid_date',
                    $this->i18n->_('messages', 'error_invaliddate'));
            return false;
        } else {
            return true;
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
        if (false === $obj) {
            $this->form_validation->set_message('_valid_time',
                    $this->i18n->_('messages', 'error_invalidtime'));
            return false;
        } else {
            return true;
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
