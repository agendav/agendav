<?php

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

use \AgenDAV\Data\Calendar as CalendarModel;

class Calendar extends MY_Controller
{

    private $calendar_colors;
    private $user;
    private $client;
    private $prefs;
    private $urlgenerator;
    private $preferences_repository;

    function __construct() {
        parent::__construct();
        $this->user = $this->container['user'];
        $this->preferences_repository = $this->container['preferences_repository'];

        if (!$this->user->isAuthenticated()) {
            $this->output->set_status_header('401');
            $this->output->_display();
            die();
        }

        $this->calendar_colors = $this->config->item('calendar_colors');

        $this->prefs = $this->preferences_repository->userPreferences($this->user->getUsername());

        $this->urlgenerator = $this->container['urlgenerator'];

        $this->client = $this->container['client'];

        $this->output->set_content_type('application/json');
    }

    function index() {
    }


    /**
     * Retrieve a list of calendars (owned by current user or shared by
     * other users with the current one)
     */
    function all() {
        $calendarfinder = $this->container['calendarfinder'];
        $calendars = $calendarfinder->getAll();
        $calendar_attrs = array();
        foreach ($calendars as $calendar => $calobj) {
            $calendar_attrs[$calendar] = $calobj->getAll();
        }
        $this->output->set_output(json_encode($calendar_attrs));
    }

    /**
     * Creates a calendar
     */
    function create() {
        $displayname = $this->input->post('displayname', true);
        $calendar_color = $this->input->post('calendar_color', true);

        // Display name
        if (empty($displayname)) {
            $this->_throw_exception($this->i18n->_('messages', 'error_calname_missing'));
        }

        // Default color
        if (empty($calendar_color)) {
            $calendar_color = '#' . $this->calendar_color[0];
        }

        // Generate URL
        $url = $this->urlgenerator->generateCalendarHomeSet($this->user->getUsername()) . $this->icshelper->generate_guid();

        // Add transparency to color
        $calendar_color = $this->toRGBA($calendar_color);

        $new_calendar = new CalendarModel(
            $url,
            $displayname
        );
        $new_calendar->color = $calendar_color;

        $res = $this->client->createCalendar($new_calendar);

        if ($res !== true) {
            switch ($res) {
                case '403':
                    $this->_throw_error($this->i18n->_('messages', 'error_denied'));
                    break;
                default:
                    $this->_throw_error($this->i18n->_('messages', 'error_unknownhttpcode'));
            }
        } else {
            $this->_throw_success();
        }
    }


    /**
     * Deletes a calendar
     */
    function delete() {
        $calendar = $this->input->post('calendar', true);
        if ($calendar === false) {
            log_message('ERROR', 'Call to delete_calendar() without calendar');
            $this->_throw_error($this->i18n->_('messages', 'error_interfacefailure'));
        }

        if (isset($shares[$calendar])) {
            $this_calendar_shares = array_values($shares[$calendar]);
            foreach ($this_calendar_shares as $k => $data) {
                $this->shared_calendars->remove($data['sid']);
            }
        }

        // Proceed to remove calendar from CalDAV server
        $res = $this->client->deleteResource($calendar, null);

        if ($res === true) {
            $this->_throw_success($calendar);
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
                    $params = array('%res' => $res);
                    break;
            }
            $this->_throw_exception($this->i18n->_('messages', $usermsg, $params));
        }
    }

    /**
     * Modifies a calendar
     */
    function modify() {
        $is_sharing_enabled =
            $this->config->item('enable_calendar_sharing');
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
                false || ($is_sharing_enabled && $is_shared_calendar === false)) {
            log_message('ERROR', 
                    'Call to modify_calendar() with incomplete parameters');
            $this->_throw_error($this->i18n->_('messages',
                        'error_interfacefailure'));
        }

        // Calculate boolean value for is_shared_calendar
        $is_shared_calendar = ($is_shared_calendar === false ?
                false :
                ($is_shared_calendar == 'true'));


        // Retrieve ID on shared calendars table
        if ($is_sharing_enabled && $is_shared_calendar) {
            $current_calendar_shares =
                $this->shared_calendars->usersWithAccessTo($calendar);
            $current_user = $this->user->getUsername();
            foreach ($current_calendar_shares as $sh) {
                if ($sh['username'] == $current_user) {
                    $sid = $sh['sid'];
                    break;
                }
            }

            if ($sid === null) {
                log_message('ERROR', 
                        'Call to modify_calendar() with shared calendar, '
                        .'but no sid was found');
                $this->_throw_error($this->i18n->_('messages',
                            'error_interfacefailure'));
            }

        }


        // Add alpha channel to color
        $calendar_color = $this->toRGBA($calendar_color);

        // Proceed to modify calendar
        if (!$is_shared_calendar) {
            // Calendar properties
            $changed_calendar = new CalendarModel(
                $calendar,
                $displayname
            );
            $changed_calendar->color = $calendar_color;

            $res = $this->client->changeResource($changed_calendar);
        } else if ($is_sharing_enabled) {
            // If this a shared calendar, store settings locally
            $props = array(
                'displayname' => $displayname,
                'color' => $calendar_color,
            );
            $success = $this->shared_calendars->store($sid,
                    null,
                    $calendar,
                    $this->user->getUsername(),
                    $props);
            if ($success !== true) {
                if ($success == '404') {
                    $this->_throw_exception($this->i18n->_('messages',
                                'error_calendarnotfound'));
                } else {
                    $this->_throw_exception(
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
            $this->_throw_exception($this->i18n->_('messages', 'error_interfacefailure'));
        }

        // Set ACLs
        if ($is_sharing_enabled && $res === true && !$is_shared_calendar) {
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
                            $this->user->getUsername(),
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
            $this->_throw_success();
        } else {
            // There was an error
            $this->_throw_exception(
                $this->i18n->_('messages', 'error_unknownhttpcode', array('%res' => $res))
            );
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
