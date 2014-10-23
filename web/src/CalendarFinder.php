<?php 
namespace AgenDAV;

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

class CalendarFinder
{
    private $logger;

    private $channels;


    public function __construct()
    {
        $this->channels = array();
    }

    public function registerChannel(\AgenDAV\CalendarChannels\IChannel $channel)
    {
        $this->channels[] = $channel;
    }

    public function getAll()
    {
        $calendars = array();

        foreach ($this->channels as $c) {
            $calendars = array_merge($calendars, $c->getCalendars());
        }

        return $calendars;
    }
}
