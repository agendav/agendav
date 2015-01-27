<?php

namespace AgenDAV\Controller\Event;

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

use AgenDAV\DateHelper;
use AgenDAV\CalDAV\Client;
use AgenDAV\EventInstance;

class Drop extends Alter
{
    /*
     * Event drop possibilities
     */
    const TIMED_TO_TIMED = 0;
    const TIMED_TO_ALLDAY = 1;
    const ALLDAY_TO_TIMED = 2;
    const ALLDAY_TO_ALLDAY = 3;

    const ALLDAY_TYPE = 1;

    /**
     * Changes the instance to reflect an event drop
     *
     * @param \AgenDAV\EventInstance $instance
     * @param \DateTimeZone $timezone
     * @param int $minutes
     * @param Array $input
     */
    protected function modifyInstance(
        EventInstance $instance,
        \DateTimeZone $timezone,
        $minutes,
        array $input = []
    )
    {
        $movement = $this->describeMovement($input['was_allday'], $input['allday']);

        $start = $instance->getStart();
        $end = $instance->getEnd();

        if ($movement === self::ALLDAY_TO_ALLDAY || $movement === self::TIMED_TO_TIMED) {
            DateHelper::addMinutesTo($start, $minutes);
            DateHelper::addMinutesTo($end, $minutes);
        }

        if ($movement === self::ALLDAY_TO_TIMED) {
            // Original event is in UTC. Switch it to user timezone
            $start = DateHelper::switchTimeZone($start, $timezone);
            DateHelper::addMinutesTo($start, $minutes);

            // defaultTimedEventDuration (Fullcalendar) is set to 1h
            $end = clone $start;
            DateHelper::addMinutesTo($end, 60);
        }

        if ($movement === self::TIMED_TO_ALLDAY) {
            // Ignore original time, switch to UTC at 00:00:00
            $start = DateHelper::getStartOfDayUTC($start);
            DateHelper::addMinutesTo($start, $minutes);

            // defaultAllDayEventDuration (Fullcalendar) is set to 1 day
            $end = clone $start;
            DateHelper::addMinutesTo($end, 60*24);
        }

        // Update start and end on instance, depending on movement
        $allday = ($movement & self::ALLDAY_TYPE) !== 0;
        $instance->setStart($start, $allday);
        $instance->setEnd($end, $allday);
    }

    /**
     * Decides which type of movement the user has done. This applies to
     * event dropping
     *
     * @param string $was_allday    Possible values: 'true' or 'false'
     * @param string $now_allday    Possible values: 'true' or 'false'
     * @return int                  One of the constants from this class
     */
    protected function describeMovement($was_allday, $now_allday)
    {
        if ($was_allday === 'false' && $now_allday === 'false') {
            return self::TIMED_TO_TIMED;
        }
        if ($was_allday === 'false' && $now_allday === 'true') {
            return self::TIMED_TO_ALLDAY;
        }
        if ($was_allday === 'true' && $now_allday === 'false') {
            return self::ALLDAY_TO_TIMED;
        }
        if ($was_allday === 'true' && $now_allday === 'true') {
            return self::ALLDAY_TO_ALLDAY;
        }

        return null;
    }
}
