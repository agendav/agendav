<?php

namespace AgenDAV\Controller\Event;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
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
use AgenDAV\EventInstance;
use Symfony\Component\HttpFoundation\ParameterBag;

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

    protected function modifyInstance(
        EventInstance $instance,
        \DateTimeZone $timezone,
        $minutes,
        ParameterBag $input
    )
    {
        $movement = $this->describeMovement(
            $input->get('was_allday'),
            $input->get('allday')
        );

        $start = $instance->getStart();
        $end = $instance->getEnd();

        if ($movement === self::ALLDAY_TO_ALLDAY || $movement === self::TIMED_TO_TIMED) {
            $start = DateHelper::addMinutesTo($start, $minutes);
            $end = DateHelper::addMinutesTo($end, $minutes);
        }

        if ($movement === self::ALLDAY_TO_TIMED) {
            $start = DateHelper::switchTimeZone($start, $timezone);
            $start = DateHelper::addMinutesTo($start, $minutes);
            $end = DateHelper::addMinutesTo($start, 60);
        }

        if ($movement === self::TIMED_TO_ALLDAY) {
            $start = DateHelper::getStartOfDayUTC($start);
            $start = DateHelper::addMinutesTo($start, $minutes);
            $end = DateHelper::addMinutesTo($start, 60 * 24);
        }

        $allday = ($movement & self::ALLDAY_TYPE) !== 0;
        $instance->setStart($start, $allday);
        $instance->setEnd($end, $allday);
    }

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
