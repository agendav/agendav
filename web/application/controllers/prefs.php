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

use AgenDAV\Data\Preferences;

class Prefs extends MY_Controller
{

    private $prefs;
    private $user;

    function __construct() {
        parent::__construct();

        $this->user = $this->container['user'];

        // Force authentication
        if (!$this->user->isAuthenticated()) {
            redirect('/login');
        }

        // Preferences
        $this->prefs = $this->user->getPreferences();
    }

    function index() {
        $calendarfinder = $this->container['calendarfinder'];

        // Layout components
        $components = array();
        $title = $this->config->item('site_title');

        $data_header = array(
                'title' => $title,
                'logged_in' => true,
                'username' => $this->user->getUsername(),
                'body_class' => array('prefspage'),
                );

        $data_calendar = array();
        $logo = $this->config->item('logo');
        $data_calendar['logo'] = custom_logo($logo, $title);
        $data_calendar['title'] = $title;

        $components['header'] = 
            $this->load->view('common_header', $data_header, true);

        $components['navbar'] = 
            $this->load->view('navbar', $data_header, true);


        // Calendar list
        $calendar_list = $calendarfinder->getAll();

        // TODO refactor this part
        $hidden_calendars = $this->prefs->hidden_calendars;
        if ($hidden_calendars === null) {
            $hidden_calendars = array();
        }

        $default_calendar = $this->prefs->default_calendar;

        $calendar_ids_and_dn = array();
        foreach ($calendar_list as $cal) {
            $calendar_ids_and_dn[$cal->calendar] = $cal->displayname;
        }

        $data_prefs = array(
                'calendar_list' => $calendar_list,
                'calendar_ids_and_dn' => $calendar_ids_and_dn,
                'default_calendar' => $default_calendar,
                'hidden_calendars' => $hidden_calendars,
                'language' => $this->config->item("language"),
                'prefs_firstday' => $this->prefs->prefs_firstday,
                'timezone' => $this->config->item("default_timezone"),
                );

        $components['content'] = $this->load->view('preferences_page',
                $data_prefs, true);
        $components['footer'] = $this->load->view('footer',
                array(
                    'load_session_refresh' => true,
                    ), true);

        $this->load->view('layouts/plain.php', $components);
    }

    /**
     * Settings currently processed by this action:
     *  - calendar@form: hidden_calendars
     *  - default_calendar@form: default_calendar
     */
    function save() {
        $calendar = $this->input->post('calendar', true);
        $default_calendar = $this->input->post('default_calendar', true);
        $prefs_firstday = $this->input->post('prefs_firstday', true);
        $language = $this->input->post('language', true);
        $timezone = $this->input->post('timezone', true);

        if (!is_array($calendar)) {
            log_message('ERROR',
                'Preferences save attempt with invalid calendars array');
            $this->_throw_error($this->i18n->_('messages', 
                        'error_interfacefailure'));
        }

        if ($default_calendar === FALSE) {
            log_message('ERROR',
                'Preferences save attempt with default_calendar not set');
            $this->_throw_error($this->i18n->_('messages', 
                        'error_interfacefailure'));
        }

        $current_user = $this->user->getUsername();
        $current_prefs = $this->user->getPreferences(true);

        // Default calendar
        $current_prefs->default_calendar = $default_calendar;

        // Calendar processing
        $hidden_calendars = array();

        foreach ($calendar as $c) {
            if (!isset($c['name'])) {
                log_message('ERROR',
                        'Skipping invalid calendar when saving preferences, '
                        .'name not found');
            } else {
                if (isset($c['hide']) && $c['hide'] == '1') {
                    $hidden_calendars[$c['name']] = true;
                }
            }
        }

        $current_prefs->hidden_calendars = $hidden_calendars;
        $current_prefs->language = $language;
        $current_prefs->timezone = $timezone;

        if (isset($prefs_firstday))
            $current_prefs->prefs_firstday = $prefs_firstday;

        // Save preferences
        $this->preferences->save($current_user, $current_prefs);

        $this->session->set_userdata('prefs', $current_prefs->getAll());
        $this->_throw_success();
    }

    // TODO: refactor these methods and caldav2json ones into a single library
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
