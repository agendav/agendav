<?php

/*
 * Copyright 2012 Jorge López Pérez <jorge@adobo.org>
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

class MY_Controller extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->container = new Pimple();

        /*
         * Make some CI models/libraries available to Pimple.
         * PHP 5.3 doesn't support the use of $this inside closures
         */
        $ci_preferences = $this->preferences;
        $ci_encrypt = $this->encrypt;
        $ci_logger = $this->extended_logs;
        $ci_shared_calendars = $this->shared_calendars;

        // Classes
        $this->container['user_class'] = '\AgenDAV\User';
        $this->container['urlgenerator_class'] = '\AgenDAV\CalDAV\URLGenerator';
        $this->container['client_class'] = '\AgenDAV\CalDAV\CURLClient';
        $this->container['session_class'] = '\AgenDAV\CodeIgniterSessionManager';

        // URLGenerator
        $cfg = array(
            'caldav_server' => $this->config->item('caldav_server'),
            'caldav_principal_url' => $this->config->item('caldav_principal_url'),
            'caldav_calendar_homeset_template' => $this->config->item('caldav_calendar_homeset_template'),
            'public_caldav_url' => $this->config->item('public_caldav_url')
        );

        $this->container['urlgenerator'] = $this->container->share(function($container) use ($cfg){
            $c = $container['urlgenerator_class'];
            return new $c(
                $cfg['caldav_server'],
                $cfg['caldav_principal_url'],
                $cfg['caldav_calendar_homeset_template'],
                $cfg['public_caldav_url']
            );
        });

        // Session
        $this->container['session'] = $this->container->share(function($container) {
            return new $container['session_class'];
        });

        // User
        $this->container['user'] = $this->container->share(function($container) use ($ci_preferences, $ci_encrypt) {
            $c = $container['user_class'];
            return new $c(
                $container['session'],
                $ci_preferences,
                $ci_encrypt
            );
        });

        // CalDAV client
        $this->container['client'] = $this->container->share(function($container) use ($ci_logger) {
            $c = $container['client_class'];
            return new $c(
                $container['user'],
                $container['urlgenerator'],
                $ci_logger,
                AgenDAV\Version::V
            );
        });
        
        // Calendar sources
        $this->container['channels/calendarhomeset'] = $this->container->share(function($container) {
            return new \AgenDAV\CalendarChannels\CalendarHomeSet($container['client']);
        });
        $this->container['channels/sharedcalendars'] = $this->container->share(function($container) use ($ci_shared_calendars) {
            $shared = new \AgenDAV\CalendarChannels\SharedCalendars($container['client'], $ci_shared_calendars);
            $user = $container['user'];
            $shared->configure(array('username' => $user->getUsername()));

            return $shared;
        });

        // Calendar finder
        $enable_calendar_sharing = $this->config->item('enable_calendar_sharing');
        $this->container['calendarfinder'] = $this->container->share(function($container) use ($enable_calendar_sharing) {
            $calendar_finder = new \AgenDAV\CalendarFinder();

            $calendar_finder->registerChannel($container['channels/calendarhomeset']);

            // Sharing enabled?
            if ($enable_calendar_sharing === true) {
                $calendar_finder->registerChannel($container['channels/sharedcalendars']);
            }

            return $calendar_finder;
        });
    }
}

