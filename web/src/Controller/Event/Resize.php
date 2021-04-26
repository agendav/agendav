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

class Resize extends Alter
{
    /**
     * Changes the instance to reflect an event resize
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
        ParameterBag $input
    )
    {
        $end = $instance->getEnd();

        $end = DateHelper::addMinutesTo($end, $minutes);
        $instance->setEnd($end, $instance->isAllDay());
    }

}
