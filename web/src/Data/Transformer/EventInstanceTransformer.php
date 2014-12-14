<?php

namespace AgenDAV\Data\Transformer;

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

use League\Fractal;
use AgenDAV\EventInstance;
use AgenDAV\CalDAV\Resource\Calendar;

class EventInstanceTransformer extends Fractal\TransformerAbstract
{
    private $calendar;

    /**
     * @param AgenDAV\CalDAV\Resource\Calendar $calendar
     */
    public function __construct(Calendar $calendar)
    {
        $this->calendar = $calendar;
    }

    public function transform(EventInstance $event)
    {
        return [
            'calendar' => $this->calendar->getUrl(),
            'url' => $event->getUrl(),
            'title' => $event->getSummary(),
            'start' => $event->getStart()->format('c'),
            'end' => $event->getEnd()->format('c'),
            'allDay' => $event->isAllDay(),
            'description' => $event->getDescription(),
        ];
    }
}
