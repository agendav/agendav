<?php

namespace AgenDAV\Event;

/*
 * Copyright 2015 Jorge López Pérez <jorge@adobo.org>
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

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * This class provides several helper methods to deal with VObject structures
 */
class VObjectHelper
{
    /**
     * Sets the base VEVENT on a VCALENDAR. A base VEVENT is understood as:
     *
     * - The only VEVENT in a non recurring event
     * - The VEVENT which has the RRULE set and no RECURRENCE-IDs assigned on a
     *   recurring event
     *
     * @param Sabre\VObject\Component\VCalendar $vcalendar
     * @param Sabre\VObject\Component\VEvent $base
     * @return void
     */
    public static function setBaseVEvent(VCalendar $vcalendar, VEvent $base)
    {
        $position = 0;

        foreach ($vcalendar->select('VEVENT') as $i => $vevent) {
            if (!isset($vevent->{'RECURRENCE-ID'})) {
                $position = $i;
                break;
            }
        }

        $vcalendar->children[$position] = $base;
    }

    /**
     * Sets an exception VEVENT on a VCALENDAR. Replaces the existing exception
     * if found, adds it otherwise.
     *
     * @param Sabre\VObject\Component\VCalendar $vcalendar
     * @param Sabre\VObject\Component\VEvent $vevent
     * @return void
     */
    public static function setExceptionVEvent(VCalendar $vcalendar, VEvent $vevent)
    {
        $recurrence_id = (string) $vevent->{'RECURRENCE-ID'};
        $existing = self::findExceptionVEvent($vcalendar, $recurrence_id);

        if ($existing !== null) {
            $vcalendar->remove($existing);
        }

        $vcalendar->add($vevent);
    }

    /**
     * Finds an existing recurrence exception by RECURRENCE-ID
     *
     * @param Sabre\VObject\Component\VCalendar $vcalendar
     * @param string $recurrence_id
     * @return \Sabre\VObject\Component\VEvent|null
     */
    public static function findExceptionVEvent(VCalendar $vcalendar, $recurrence_id)
    {
        foreach ($vcalendar->VEVENT as $vevent) {
            $current_recurrence_id = (string)$vevent->{'RECURRENCE-ID'};
            if ($current_recurrence_id === $recurrence_id) {
                return $vevent;
            }
        }

        return null;
    }
}

