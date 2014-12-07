<?php

namespace AgenDAV\Event;

/*
 * Copyright 2014 Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\Event;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * VObject implementation of Events
 *
 */
class VObjectEvent extends Event
{
    protected $vcalendar;

    protected $is_expanded;

    protected $is_recurrent;

    /**
     * @param mixed VCalendar $vcalendar
     */
    public function __construct(VCalendar $vcalendar, $is_expanded = false)
    {
        $this->vcalendar = $vcalendar;

        $this->is_expanded = $is_expanded;
        $this->is_recurrent = $this->checkIfRecurrent($is_expanded);
    }

    public function getSummary()
    {
    }

    public function getLocation()
    {
    }

    public function getDescription()
    {
    }

    public function getStart()
    {
    }

    public function getEnd()
    {
    }

    public function isAllDay()
    {
    }

    protected function checkIfRecurrent($is_expanded)
    {
        $count = count($this->vcalendar->VEVENT);

        $vevent_0 = $this->vcalendar->VEVENT[0];

        if ($is_expanded === true || $count > 1 || isset($vevent_0->RRULE)) {
            return true;
        }

        return false;
    }
}

