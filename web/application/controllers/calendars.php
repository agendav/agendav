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

use League\Fractal\Resource\Collection;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Transformer\CalendarTransformer;
use AgenDAV\Uuid;

class Calendars extends MY_Controller
{

    private $current_username;

    private $client;

    protected $calendar_home_set;

    protected $sharing_enabled;

    public function __construct()
    {
        parent::__construct();

        if (!$this->container['session']->isAuthenticated()) {
            $this->output->set_status_header('401');
            $this->output->_display();
            die();
        }

        $this->sharing_enabled = $this->config->item('enable_calendar_sharing');

        $this->current_username = $this->container['session']->get('username');
        $this->calendar_home_set = $this->container['session']->get('calendar_home_set');

        $this->client = $this->container['caldav_client'];

        $this->output->set_content_type('application/json');
    }

    /**
     * Retrieve a list of calendars (owned by current user or shared by
     * other users with the current one)
     */
    public function index()
    {
        $calendars = $this->container['calendar_finder']->getCalendars();

        $fractal = $this->container['fractal'];
        $collection = new Collection($calendars, new CalendarTransformer, 'calendars');

        $this->output->set_output($fractal->createData($collection)->toJson());
    }

    /**
     * Creates a calendar
     */
    public function create()
    {
        $displayname = $this->input->post('displayname', true);
        $calendar_color = $this->input->post('calendar_color', true);

        // Display name
        if (empty($displayname)) {
            $this->answerWithException($this->i18n->_('messages', 'error_calname_missing'));
        }

        // Default color
        if (empty($calendar_color)) {
            $calendar_color = '#' . $this->calendar_color[0];
        }

        // Generate URL
        $url = $this->calendar_home_set . Uuid::generate();

        // Add transparency to color
        $calendar_color = $this->toRGBA($calendar_color);

        $new_calendar = new Calendar(
            $url,
            [
                Calendar::DISPLAYNAME => $displayname,
                Calendar::COLOR  => $calendar_color,
            ]
        );

        try {
            $this->client->createCalendar($new_calendar);
        } catch (\Exception $e) {
            $this->answerWithError($this->i18n->_('messages', 'error_denied'));
            return;
        }

        $this->answerWithSuccess();
    }


    /**
     * Deletes a calendar
     */
    public function delete()
    {
        $calendar = $this->input->post('calendar', true);
        if ($calendar === false) {
            log_message('ERROR', 'Call to delete_calendar() without calendar');
            $this->answerWithError($this->i18n->_('messages', 'error_interfacefailure'));
        }

        $calendar = new Calendar($calendar);

        if (isset($shares[$calendar])) {
            $this_calendar_shares = array_values($shares[$calendar]);
            foreach ($this_calendar_shares as $k => $data) {
                $this->shared_calendars->remove($data['sid']);
            }
        }

        // Proceed to remove calendar from CalDAV server
        try {
            $this->client->deleteCalendar($calendar);
        } catch (\Exception $e) {
            $this->answerWithError($this->i18n->_('messages', 'error_denied'));
            return;
        }
        $this->answerWithSuccess($calendar->getUrl());
    }

    /**
     * Modifies a calendar
     */
    public function save()
    {
        $calendar = $this->input->post('calendar', true);
        $displayname = $this->input->post('displayname', true);
        $calendar_color = $this->input->post('calendar_color', true);

        $is_shared_calendar = $this->input->post('is_shared_calendar', true);

        // Will be only used for shared calendars
        $sid = null;

        // In case this calendar is owned by current user, this will contain
        // a list of users he/she wants to share the calendar with
        $share_with = $this->input->post('share_with', true);

        if ($calendar === false || $displayname === false || $calendar_color ===
                false || ($this->sharing_enabled && $is_shared_calendar === false)) {
            log_message('ERROR', 
                    'Call to modify_calendar() with incomplete parameters');
            $this->answerWithError($this->i18n->_('messages',
                        'error_interfacefailure'));
        }

        // Calculate boolean value for is_shared_calendar
        $is_shared_calendar = $is_shared_calendar === 'true';


        // Retrieve ID on shared calendars table
        if ($this->sharing_enabled && $is_shared_calendar) {
            $current_calendar_shares =
                $this->shared_calendars->usersWithAccessTo($calendar);
            foreach ($current_calendar_shares as $sh) {
                if ($sh['username'] === $this->current_username) {
                    $sid = $sh['sid'];
                    break;
                }
            }

            if ($sid === null) {
                log_message('ERROR', 
                        'Call to modify_calendar() with shared calendar, '
                        .'but no sid was found');
                $this->answerWithError($this->i18n->_('messages',
                            'error_interfacefailure'));
            }

        }


        // Add alpha channel to color
        $calendar_color = $this->toRGBA($calendar_color);

        // Proceed to modify calendar
        if (!$is_shared_calendar) {
            // Calendar properties
            $changed_calendar = new Calendar(
                $calendar,
                [
                    Calendar::DISPLAYNAME => $displayname,
                    Calendar::COLOR  => $calendar_color,
                ]
            );

            $res = true;
            try {
                $this->client->updateCalendar($changed_calendar);
            } catch (\Exception $e) {
                $res = false;
            }
        } else if ($this->sharing_enabled) {
            // If this a shared calendar, store settings locally
            $props = array(
                'displayname' => $displayname,
                'color' => $calendar_color,
            );
            $success = $this->shared_calendars->store($sid,
                    null,
                    $calendar,
                    $this->current_username,
                    $props);
            if ($success !== true) {
                if ($success == '404') {
                    $this->answerWithException($this->i18n->_('messages',
                                'error_calendarnotfound'));
                } else {
                    $this->answerWithException(
                            $this->i18n->_('messages', 'error_unknownhttpcode', array('%res' => $success))
                    );
                }
            } else {
                $res = true;
            }
        } else {
            // Tried to modify a shared calendar when sharing is disabled
            log_message('ERROR',
                    'Tried to modify the shared calendar ' . $calendar .' when calendar sharing is disabled');
            $this->answerWithException($this->i18n->_('messages', 'error_interfacefailure'));
        }

        // Set ACLs
        if ($this->sharing_enabled && $res === true && !$is_shared_calendar) {
            $set_shares = array();

            if (is_array($share_with) && isset($share_with['sid']) 
                    && isset($share_with['username'])
                    && isset($share_with['rw'])) {
                $num_shares = count($share_with['sid']);
                for ($i=0;$i<$num_shares;$i++) {
                    $exists_username =
                        isset($share_with['username'][$i]);
                    $exists_write_access =
                        isset($share_with['rw'][$i]);
                    if (!$exists_username || !$exists_write_access) {
                        log_message('ERROR', 
                                'Ignoring incomplete share row ('.$i.') attributes'
                                .' on calendar modification: '
                                . serialize($share_with));
                    } else {
                        $new_share = array(
                                'username' => $share_with['username'][$i],
                                'rw' => $share_with['rw'][$i],
                                );

                        if (!empty($share_with['sid'][$i])) {
                            $new_share['sid'] = $share_with['sid'][$i];
                        }

                        $set_shares[] = $new_share;
                    }
                }
            }

            $aclgenerator = $this->container['aclgenerator'];
            foreach ($set_shares as $share) {
                $principal = $this->urlgenerator->generatePrincipal($share['username']);
                if ($share['rw'] == '0') {
                    $aclgenerator->addGrant($principal, 'read');
                } else {
                    $aclgenerator->addGrant($principal, 'read_write');
                }
            }
            $res = $this->client->setACL(
                $changed_calendar->url,
                $aclgenerator->buildACL()
            );

            // Update shares on database
            if ($res === true) {
                $current_shares =
                    $this->shared_calendars->usersWithAccessTo($calendar);
                $orig_sids = array();
                foreach ($current_shares as $db_share_row) {
                    $orig_sids[$db_share_row['sid']] = true;
                }

                $updated_sids = array();
                foreach ($set_shares as $share) {
                    $this_sid = isset($share['sid']) ?
                                $share['sid'] : null;

                    $this->shared_calendars->store(
                            $this_sid,
                            $this->current_username,
                            $internal_calendar,
                            $share['username'],
                            null,                   // Preserve options
                            $share['rw']);

                    if (!is_null($this_sid)) {
                        $updated_sids[$this_sid] = true;
                    }
                }

                // Removed shares
                foreach (array_keys($orig_sids) as $sid) {
                    if (!isset($updated_sids[$sid])) {
                        $this->shared_calendars->remove($sid);
                    }
                }
            }
        }

        if ($res === true) {
            $this->answerWithSuccess();
        } else {
            // There was an error
            $this->answerWithError($this->i18n->_('messages', 'error_denied'));
        }

    }

    /**
     * Throws an exception message
     */
    private function answerWithException($message)
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
    private function answerWithError($message)
    {
        $this->output->set_status_header(500);
        $this->output->set_output(json_encode(array(
                        'result' => 'ERROR',
                        'message' => $message)));
        $this->output->_display();
        die();
    }

    /**
     * Throws a success message
     */
    private function answerWithSuccess($message = '')
    {
        $this->output->set_status_header(200);
        $this->output->set_output(json_encode(array(
                        'result' => 'SUCCESS',
                        'message' => $message)));
        $this->output->_display();
        die();
    }

    /**
     * Converts a RGB hexadecimal string (#rrggbb or short #rgb) to full
     * RGBA
     *
     * @param string $s RGB color (#rrggbb) to be converted
     * @return string RGBA representation of $s with full opacity
     */
    private function toRGBA($s)
    {
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
}
