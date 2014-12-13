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

use AgenDAV\ExpandedEvent;
use AgenDAV\Event;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * VObject implementation of expanded events (event instances)
 */

class VObjectExpandedEvent implements ExpandedEvent
{

    protected $vevent;

    /**
     * @param mixed $vevent
     * @param mixed $is_recurrent
     */
    public function __construct(
        VEvent $vevent
    )
    {
        $this->vevent = $vevent;
        $this->is_recurrent = isset($vevent->RRULE);
    }

    public function isRecurrent()
    {
        return $this->is_recurrent;
    }

    public function getSummary()
    {
        return $this->vevent->SUMMARY;
    }

    public function getLocation()
    {
        return $this->vevent->LOCATION;
    }

    public function getDescription()
    {
        return $this->vevent->DESCRIPTION;
    }

    public function getStart()
    {
        return $this->vevent->DTSTART->getDateTime();
    }

    public function getEnd()
    {
        if (isset($this->vevent->DTEND)) {
            return $this->vevent->DTEND->getDateTime();
        }
        // This is the starting point for every other case
        $end = $this->getStart();

        if (isset($this->vevent->DURATION)) {
            $end->add(DateTimeParser::parseDuration($this->vevent->DURATION));
            return $end;
        }

        if ($this->isAllDay()) {
            $end->modify('+1 day');
            return $end;
        }

        return $end;
    }

    public function isAllDay()
    {
        if (!$this->vevent->DTSTART->hasTime()) {
            return true;
        }

        return false;
    }
}
