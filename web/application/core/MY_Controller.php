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

use \AgenDAV\Data\Permissions;
use \AgenDAV\Data\SinglePermission;

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
        $ci_logger = $this->log;
        $ci_shared_calendars = $this->shared_calendars;
        $enable_calendar_sharing = $this->config->item('enable_calendar_sharing');

        // Classes
        $this->container['user_class'] = '\AgenDAV\User';
        $this->container['urlgenerator_class'] = '\AgenDAV\CalDAV\URLGenerator';
        $this->container['client_class'] = '\AgenDAV\CalDAV\CURLClient';
        $this->container['session_class'] = '\AgenDAV\CodeIgniterSessionManager';

        // URLGenerator
        $cfg = array(
            'caldav_base_url' => $this->config->item('caldav_base_url'),
            'caldav_principal_template' => $this->config->item('caldav_principal_template'),
            'caldav_calendar_homeset_template' => $this->config->item('caldav_calendar_homeset_template'),
            'caldav_public_base_url' => $this->config->item('caldav_public_base_url')
        );

        $this->container['urlgenerator'] = $this->container->share(function($container) use ($cfg){
            $c = $container['urlgenerator_class'];
            return new $c(
                $cfg['caldav_base_url'],
                $cfg['caldav_principal_template'],
                $cfg['caldav_calendar_homeset_template'],
                $cfg['caldav_public_base_url']
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

        /** @var \AgenDAV\User $user */
        $user = $this->container['user'];
        $timezone = $user->getPreferences()->timezone !== null ? $user->getPreferences()->timezone : $this->config->item('default_timezone');
        // Set default timezone used all over in this app.
        $this->config->set_item('default_timezone', $timezone);
        date_default_timezone_set($timezone);

        // Load it here instead of autoloading it becuase we need the user in here.
        $this->load->model('i18n');

        // CalDAV client
        $cfg_client = array(
            'auth' => $this->config->item('caldav_http_auth_method'),
            'useragent' => 'AgenDAV v' . \AgenDAV\Version::V,
        );
        $this->container['client'] = $this->container->share(function($container) use ($ci_logger, $cfg_client) {
            $c = $container['client_class'];
            return new $c(
                $container['user'],
                $container['urlgenerator'],
                $ci_logger,
                $cfg_client
            );
        });

        // Calendar sources
        $this->container['channels/calendarhomeset'] = $this->container->share(function($container) use ($enable_calendar_sharing, $ci_shared_calendars) {
            $homeset = new \AgenDAV\CalendarChannels\CalendarHomeSet($container['client']);
            if ($enable_calendar_sharing === true) {
                $homeset->configure(array('shares' => $ci_shared_calendars));
            }
            return $homeset;
        });
        $this->container['channels/sharedcalendars'] = $this->container->share(function($container) use ($ci_shared_calendars) {
            $shared = new \AgenDAV\CalendarChannels\SharedCalendars($container['client'], $ci_shared_calendars);
            $user = $container['user'];
            $shared->configure(array('username' => $user->getUsername()));

            return $shared;
        });

        // Calendar finder
        $this->container['calendarfinder'] = $this->container->share(function($container) use ($enable_calendar_sharing) {
            $calendar_finder = new \AgenDAV\CalendarFinder();

            $calendar_finder->registerChannel($container['channels/calendarhomeset']);

            // Sharing enabled?
            if ($enable_calendar_sharing === true) {
                $calendar_finder->registerChannel($container['channels/sharedcalendars']);
            }

            return $calendar_finder;
        });

        // ACL generator
        if ($enable_calendar_sharing === true) {
            $cfg_permissions = $this->config->item('permissions');
            $permissions = new Permissions($cfg_permissions['default']);
            foreach ($cfg_permissions as $profile => $perms) {
                $permissions->addProfile($profile, $perms);
            }
            $this->container['aclgenerator'] = $this->container->share(function($container) use ($permissions) {
                return new \AgenDAV\CalDAV\ACLGenerator($permissions);
            });
        }
    }
}

