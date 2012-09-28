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

class Client extends \CalDAVClient
{
    private $app_user;

    private $urlgenerator;

    private $logger;

    function __construct($app_user, $urlgenerator, $logger)
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
    }
}
