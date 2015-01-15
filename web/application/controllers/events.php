<?php

/*
 * Copyright 2011-2015 Jorge López Pérez <jorge@adobo.org>
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
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\Event\FullCalendarEvent;
use AgenDAV\Data\Transformer\FullCalendarEventTransformer;
use AgenDAV\Data\Serializer\PlainSerializer;;
use AgenDAV\DateHelper;
use League\Fractal\Resource\Collection;

class Events extends MY_Controller
{

    private $tz;
    private $tz_utc;

    private $client;

    public function __construct()
    {
        parent::__construct();

        if (!$this->container['session']->isAuthenticated()) {
            $this->output->set_status_header('401');
            $this->output->_display();
            die();
        }
        $this->client = $this->container['caldav_client'];

        $this->tz = $this->timezonemanager->getTz(
                $this->config->item('default_timezone'));
        $this->tz_utc = $this->timezonemanager->getTz('UTC');

        $this->output->set_content_type('application/json');
    }

    /**
     * Deletes an event
     * TODO: control whether we want to remove a single recurrence-id
     * instead of the whole event
     */
    public function delete()
    {
        $calendar = $this->input->post('calendar', true);
        $uid = $this->input->post('uid', true);
        $href = $this->input->post('href', true);
        $etag = $this->input->post('etag', true);

        $response = array();

        if ($calendar === false || $uid === false || $href === false ||
                $etag === false || empty($calendar) || empty($uid) ||
                empty($href) || empty($calendar) || empty($etag)) {
            log_message('ERROR', 'Call to delete_event() with no calendar, uid, href or etag');
            $this->_throw_error($this->i18n->_('messages',
                        'error_interfacefailure'));
        } else {
            // Simulate an event on $href
            $object = new CalendarObject($href);
            try {
                $res = $this->client->deleteCalendarObject($object);
            } catch (\Exception $e) {
                $usermsg = 'error_unknownhttpcode';
                $params['%res'] = $res->getStatusCode();
                $msg = $this->i18n->_('messages', $usermsg, $params);
                $this->_throw_exception($msg);
                return;
            }
            $this->_throw_success();
        }
    }

    /**
     * Creates or modifies an existing event
     * TODO: detect if we are defining a new recurrence-id
     */
    public function save()
    {
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
            $start = DateHelper::frontEndToDateTime($p['start'], $this->tz_utc);
            $end = DateHelper::frontEndToDateTime($p['end'], $this->tz_utc);

            // Add 1 day (iCalendar needs this)
            $end->add(new DateInterval('P1D'));
        } else {
            if ($this->form_validation->run() === false) {
                $this->_throw_exception(validation_errors());
            }

            // 2. Check if start date <= end date
            $start = DateHelper::frontEndToDateTime($p['start'], $tz);
            $end = DateHelper::frontEndToDateTime($p['end'], $tz);

            if ($end->getTimestamp() < $start->getTimestamp()) {
                $this->_throw_exception($this->i18n->_('messages',
                            'error_startgreaterend'));
            }
        }

        $p['dtstart'] = $start;
        $p['dtend'] = $end;

        // RRULE (iCalcreator needs it like this)
        if (!empty($p['rrule'])) {
            parse_str(strtr($p['rrule'], ';', '&'), $sliced_rrule);

            // iCalcreator and its API, great as usual
            if (isset($sliced_rrule['BYDAY'])) {
                $all_values = preg_split('/,/', $sliced_rrule['BYDAY']);
                $sliced_rrule['BYDAY'] = array_map(
                    function($day) { return ['DAY' => $day]; },
                    $all_values
                );
            }

            $p['rrule'] = $sliced_rrule;
        }

        // Reminders
        $reminders = array();

        // Contains a list of old parseable (visible on UI) reminders.
        // Used to remove reminders that were deleted by user
        $visible_reminders = isset($p['visible_reminders']) ?
            $p['visible_reminders'] : array();

        if (isset($p['reminders']) && is_array($p['reminders'])) {
            $data_reminders = $p['reminders'];
            $num_reminders = count($data_reminders['count']);

            for ($i=0;$i<$num_reminders;$i++) {
                $reminder_params = [
                    'position' => $data_reminders['position'][$i],
                    'count' => $data_reminders['count'][$i],
                    'unit' => $data_reminders['unit'][$i],
                ];
                $this_reminder = Reminder::createFromInput($reminder_params);

                $reminders[] = $this_reminder;
            }
        }

        // Is this a new event or a modification?

        // Valid destination calendar? 
        try {
            $dest_calendar = $this->client->getCalendarByUrl($p['calendar']);
        } catch (\UnexpectedValueException $e) {
            $this->_throw_exception(
                    $this->i18n->_('messages', 'error_calendarnotfound', array('%calendar' => $p['calendar']))
            );
            return;
        }

        if (!isset($p['modification'])) {
            // New event (resource)
            $new_uid = $this->icshelper->new_resource($p,
                    $resource, $this->tz, $reminders);
            $href = $dest_calendar->getUrl() . $new_uid . '.ics';
            $etag = '*';
        } else {
            // Load existing resource
            // Valid original calendar?
            try {
                $original_calendar = $this->client->getCalendarByUrl($p['original_calendar']);
            } catch (\UnexpectedValueException $e) {
                $this->_throw_exception(
                    $this->i18n->_('messages', 'error_calendarnotfound', array('%calendar' => $p['original_calendar']))
                );
                return;
            }

            $uid = $p['uid'];
            $orig_href = $p['href'];
            $href = $dest_calendar->getUrl() . $uid . '.ics';
            $etag = $p['etag'];

            try {
                $original_object = $this->client->fetchObjectByUid($original_calendar, $uid);
            } catch (\UnexpectedValueException $e) {
                $this->_throw_error($this->i18n->_('messages', 'error_eventnotfound'));
                return;
            }

            if ($etag != $original_object->getEtag()) {
                $this->_throw_error($this->i18n->_('messages', 'error_eventchanged'));
                return;
            }


            $resource = $this->icshelper->parse_icalendar($original_object->getContents());
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

            $properties['rrule'] = $p['rrule'];

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
            if ($original_calendar->getUrl() !== $dest_calendar->getUrl()) {
                // We will need this etag later
                $original_etag = $etag;
                $etag = '*';
            }
        }

        $new_object = new CalendarObject($href, $resource->createCalendar());
        $new_object->setCalendar($dest_calendar);
        $new_object->setEtag($etag);
        // PUT on server
        try {
            $this->client->uploadCalendarObject($new_object);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $code = $e->getResponse()->getStatusCode();
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

            return;
        }

        // Remove original event
        if (isset($p['modification']) && $original_calendar->getUrl() !== $dest_calendar->getUrl()) {
            try {
                $this->client->deleteCalendarObject($original_object);
            } catch (\Exception $e) {
                $this->_throw_exception('');
            }
        }

        // Return a list of affected calendars (original_calendar, new
        // calendar)
        $affected_calendars = array($dest_calendar->getUrl());
        if (isset($original_calendar) && $original_calendar->getUrl() !== $dest_calendar->getUrl()) {
            $affected_calendars[] = $original_calendar->getUrl();
        }

        $this->_throw_success($affected_calendars);
    }


    /**
     * Throws an exception message
     */
    private function _throw_exception($message)
    {
        $this->output->set_output(json_encode(array(
                        'result' => 'EXCEPTION',
                        'message' => $message)));
        $this->output->_display();
        die();
    }

    /**
     * Throws an error message
     */
    private function _throw_error($message)
    {
        $this->output->set_output(json_encode(array(
                        'result' => 'ERROR',
                        'message' => $message)));
        $this->output->_display();
        die();
    }

    /**
     * Throws a success message
     */
    private function _throw_success($message = '')
    {
        $this->output->set_output(json_encode(array(
                        'result' => 'SUCCESS',
                        'message' => $message)));
        $this->output->_display();
        die();
    }



}
