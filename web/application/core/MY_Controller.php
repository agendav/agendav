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
        $ci_encrypt = $this->encrypt;
        $ci_logger = $this->log;
        $ci_shared_calendars = $this->shared_calendars;
        $enable_calendar_sharing = $this->config->item('enable_calendar_sharing');


        // Database connection
        $db_options = $this->config->item('db');
        $this->container['db'] = $this->container->share(function($container) use ($db_options) {
            $db = new \AgenDAV\DB($db_options);

            return $db->getConnection();
        });

        // Preferences repository
        $this->container['preferences_repository'] = $this->container->share(function($container) {
            $db = $container['db'];
            return new AgenDAV\Repositories\DoctrinePreferencesRepository($db);
        });

        // URL generator
        $cfg = array(
            'caldav_base_url' => $this->config->item('caldav_base_url'),
            'caldav_principal_template' => $this->config->item('caldav_principal_template'),
            'caldav_calendar_homeset_template' => $this->config->item('caldav_calendar_homeset_template'),
            'caldav_public_base_url' => $this->config->item('caldav_public_base_url')
        );

        $this->container['urlgenerator'] = $this->container->share(function($container) use ($cfg){
            return new \AgenDAV\URL(
                $cfg['caldav_base_url'],
                $cfg['caldav_principal_template'],
                $cfg['caldav_calendar_homeset_template'],
                $cfg['caldav_public_base_url']
            );
        });

        // Session stuff
        $session_options = $this->config->item('sessions');
        $this->container['session_storage'] = $this->container->share(function($container) use ($session_options) {
            $storage = new Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage(
                $session_options
            );

            return $storage;
        });

        $this->container['session'] = $this->container->share(function($container) {
            return new \AgenDAV\Session\HttpFoundationSession($container['session_storage']);
        });

        $this->container['session']->initialize();

        // User
        $this->container['user'] = $this->container->share(function($container) use ($ci_encrypt) {
            return new \AgenDAV\User(
                $container['session'],
                $ci_encrypt
            );
        });

        // CalDAV client
        $cfg_client = array(
            'auth' => $this->config->item('caldav_http_auth_method'),
            'useragent' => 'AgenDAV v' . \AgenDAV\Version::V,
        );
        $this->container['client'] = $this->container->share(function($container) use ($ci_logger, $cfg_client) {
            return new \AgenDAV\CalDAV\CURLClient(
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

