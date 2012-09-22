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

use AgenDAV\User;

class Calendar extends CI_Controller {

    private $calendar_colors;
    private $user, $prefs;

    function __construct() {
        parent::__construct();
        $this->user = User::getInstance();

        if (!$this->user->isAuthenticated()) {
            $this->extended_logs->message('INFO', 'Anonymous access attempt to ' . uri_string());
            $this->output->set_status_header('401');
            $this->output->_display();
            die();
        }

        $this->calendar_colors = $this->config->item('calendar_colors');

        $this->prefs = $this->preferences->get($this->user->getUsername());

        $this->caldavoperations->setClient($this->user->createCalDAVClient());

        $this->output->set_content_type('application/json');
    }

    function index() {
    }


    /**
     * Retrieve a list of calendars (owned by current user or shared by
     * other users with the current one)
     */
    function all() {
        $calendars = $this->user->allCalendars(true);
        $this->output->set_output(json_encode($calendars));
    }

    /**
     * Creates a calendar
     */
    function create() {
        $displayname = $this->input->post('displayname', true);
        $calendar_color = $this->input->post('calendar_color', true);

        // Display name
        if (empty($displayname)) {
            $this->_throw_exception($this->i18n->_('messages',
                        'error_calname_missing'));
        }

        // Default color
        if (empty($calendar_color)) {
            $calendar_color = '#' . $this->calendar_color[0];
        }

        // Generate internal calendar name
        $calendar = $this->caldavoperations->findCalendarHomeSet() . $this->icshelper->generate_guid();

        // Add transparency to color
        $calendar_color = $this->caldavoperations->rgb2rgba($calendar_color);

        // Calendar properties
        $props = array(
                'displayname' => $displayname,
                'color' => $calendar_color,
                );


        $res = $this->caldavoperations->createCalendar($calendar, $props);

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
        $calendar = $this->input->post('calendar');
        if ($calendar === false) {
            $this->extended_logs->message('ERROR', 'Call to delete_calendar() without calendar');
            $this->_throw_error($this->i18n->_('messages', 'error_interfacefailure'));
        }

        if (isset($shares[$calendar])) {
            $this_calendar_shares = array_values($shares[$calendar]);
            foreach ($this_calendar_shares as $k => $data) {
                $this->shared_calendars->remove($data['sid']);
            }
        }

        // Proceed to remove calendar from CalDAV server
        $res = $this->caldavoperations->deleteResource($calendar, null);

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
        $calendar = $this->input->post('calendar');
        $displayname = $this->input->post('displayname');
        $calendar_color = $this->input->post('calendar_color');

        $is_shared_calendar = $this->input->post('is_shared_calendar');

        // Will be only used for shared calendars
        $sid = null;

        // In case this calendar is owned by current user, this will contain
        // a list of users he/she wants to share the calendar with
        $share_with = $this->input->post('share_with');

        if ($calendar === false || $displayname === false || $calendar_color ===
                false || ($is_sharing_enabled && $is_shared_calendar === false)) {
            $this->extended_logs->message('ERROR', 
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
                $this->shared_calendars->users_with_access_to($calendar);
            $current_user = $this->user->getUsername();
            foreach ($current_calendar_shares as $sh) {
                if ($sh['username'] == $current_user) {
                    $sid = $sh['sid'];
                    break;
                }
            }

            if ($sid === null) {
                $this->extended_logs->message('ERROR', 
                        'Call to modify_calendar() with shared calendar, '
                        .'but no sid was found');
                $this->_throw_error($this->i18n->_('messages',
                            'error_interfacefailure'));
            }

        }


        // Add transparency to color
        $calendar_color = $this->caldavoperations->rgb2rgba($calendar_color);

        // Calendar properties
        $props = array(
                'displayname' => $displayname,
                'color' => $calendar_color,
                );


        // Proceed to modify calendar
        if (!$is_shared_calendar) {

            $res = $this->caldavoperations->changeProperties($calendar, $props);
        } else if ($is_sharing_enabled) {
            // If this a shared calendar, store settings locally
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
            $this->extended_logs->message('ERROR',
                    'Tried to modify the shared calendar ' . $calendar .' when calendar sharing is disabled');
            $this->_throw_exception($this->i18n->_('messages', 'error_interfacefailure'));
        }

        // Set ACLs
        if ($is_sharing_enabled && $res === true && !$is_shared_calendar) {
            $set_shares = array();

            if (is_array($share_with) && isset($share_with['sid']) 
                    && isset($share_with['username'])
                    && isset($share_with['write_access'])) {
                $num_shares = count($share_with['sid']);
                for ($i=0;$i<$num_shares;$i++) {
                    $exists_username =
                        isset($share_with['username'][$i]);
                    $exists_write_access =
                        isset($share_with['write_access'][$i]);
                    if (!$exists_username || !$exists_write_access) {
                        $this->extended_logs->message('ERROR', 
                                'Ignoring incomplete share row ('.$i.') attributes'
                                .' on calendar modification: '
                                . serialize($share_with));
                    } else {
                        $new_share = array(
                                'username' => $share_with['username'][$i],
                                'write_access' => $share_with['write_access'][$i],
                                );

                        if (!empty($share_with['sid'][$i])) {
                            $new_share['sid'] = $share_with['sid'][$i];
                        }

                        $set_shares[] = $new_share;
                    }
                }
            }

            $res = $this->caldavoperations->setacl(
                    $this->user->getUsername(),
                    $this->user->getPasswd(),
                    $internal_calendar,
                    $set_shares);

            // Update shares on database
            if ($res === true) {
                $current_shares =
                    $this->shared_calendars->users_with_access_to($calendar);
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
                            $share['write_access']);

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
            $this->_throw_exception($this->i18n->_('messages', $res[0], $res[1]));
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
