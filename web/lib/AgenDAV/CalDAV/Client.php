<?php

namespace AgenDAV\CalDAV;

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

use AgenDAV\Version;

/**
 * AgenDAV CalDAV client 
 */
class Client extends \CalDAVClient
{
    /**
     * Current user
     *
     * @var Object
     * @access private
     */
    private $app_user;

    /**
     * URL generator manager 
     *
     * @var Object
     * @access private
     */
    private $urlgenerator;

    /**
     * Log manager 
     *
     * @var Object
     * @access private
     */
    private $logger;

    /**
     * Creates a new CalDAV client
     *
     * @param Object $app_user Current user
     * @param Object $urlgenerator URL generator
     * @param Object $logger Log manager
     * @param string $version AgenDAV version
     * @access public
     * @return void
     */
    public function __construct($app_user, $urlgenerator, $logger, $version)
    {
        $this->app_user = $app_user;
        $this->urlgenerator = $urlgenerator;
        $this->logger = $logger;

        // TODO auth options
        parent::__construct(
            $this->urlgenerator->getBaseURL(),
            $this->app_user->getUserName(),
            $this->app_user->getPasswd()
        );

        $this->PrincipalURL($this->urlgenerator->generatePrincipal($this->app_user->getUserName()));
        $this->CalendarHomeSet($this->urlgenerator->generateCalendarHomeSet($this->app_user->getUserName()));
        $this->SetUserAgent('AgenDAV v' . $version);
    }
}
