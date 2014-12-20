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
use AgenDAV\EventInstance;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * VObject implementation of expanded events (event instances)
 */

class VObjectEventInstance implements EventInstance
{

    protected $vevent;

    /**
     * @param mixed $vevent
     */
    public function __construct(
        VEvent $vevent
    )
    {
        $this->vevent = $vevent;
        $this->is_recurrent = isset($vevent->RRULE);
    }

    public function getUid()
    {
        return (string) $this->vevent->UID;
    }

    public function isRecurrent()
    {
        return $this->is_recurrent;
    }

    public function getSummary()
    {
        return (string) $this->vevent->SUMMARY;
    }

    public function getLocation()
    {
        return (string) $this->vevent->LOCATION;
    }

    public function getDescription()
    {
        return (string) $this->vevent->DESCRIPTION;
    }

    public function getStart()
    {
        return $this->vevent->DTSTART->getDateTime();
    }

    public function getTimeZone()
    {
        return $this->getStart()->getDateTimeZone();
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

    public function getRecurrenceRule()
    {
        return (string) $this->vevent->RRULE;
    }

    public function getClass()
    {
        return (string) $this->vevent->CLASS;
    }

    public function getTransp()
    {
        return (string) $this->vevent->TRANSP;
    }

    public function getRecurrenceId()
    {
        return (string) $this->vevent->{'RECURRENCE-ID'};
    }

    public function isAllDay()
    {
        if (!$this->vevent->DTSTART->hasTime()) {
            return true;
        }

        return false;
    }
}
