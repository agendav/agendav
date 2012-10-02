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

        // Classes
        $this->container['user_class'] = '\AgenDAV\User';
        $this->container['urlgenerator_class'] = '\AgenDAV\CalDAV\URLGenerator';
        $this->container['client_class'] = '\AgenDAV\CalDAV\CURLClient';
        $this->container['session_class'] = '\AgenDAV\CodeIgniterSessionManager';

        // URLGenerator
        $this->container['urlgenerator'] = $this->container->share(function($container) {
            $c = $container['urlgenerator_class'];
            return new $c(
                $this->config->item('caldav_server'),
                $this->config->item('caldav_principal_url'),
                $this->config->item('caldav_calendar_homeset_template'),
                $this->config->item('public_caldav_url')
            );
        });

        // Session
        $this->container['session'] = $this->container->share(function($container) {
            return new $container['session_class'];
        });

        // User
        $this->container['user'] = $this->container->share(function($container) {
            $c = $container['user_class'];
            return new $c(
                $container['session'],
                $this->preferences,
                $this->encrypt
            );
        });

        // CalDAV client
        $this->container['client'] = $this->container->share(function($container) {
            $c = $container['client_class'];
            return new $c(
                $container['user'],
                $container['urlgenerator'],
                $this->extended_logs,
                AgenDAV\Version::V
            );
        });
    }
}

