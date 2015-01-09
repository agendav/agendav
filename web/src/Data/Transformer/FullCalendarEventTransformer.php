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
use AgenDAV\Event\FullCalendarEvent;

class FullCalendarEventTransformer extends Fractal\TransformerAbstract
{
    public function transform(FullCalendarEvent $fc_event)
    {
        $event = $fc_event->getEvent();
        $result = [
            'calendar' => $fc_event->getCalendarUrl(),
            'href' => $fc_event->getUrl(),
            'etag' => $fc_event->getEtag(),
            'uid' => $event->getUid(),
            'title' => $event->getSummary(),
            'start' => $event->getStart()->format('c'),
            'end' => $event->getEnd()->format('c'),
            'allDay' => $event->isAllDay(),
            // TODO think about going without orig_allday
            'orig_allday' => $event->isAllDay(),
            'location' => $event->getLocation(),
            'icalendar_class' => $event->getClass(),
            'transp' => $event->getTransp(),
            'description' => $event->getDescription(),
        ];

        $result['id'] = $result['calendar'] . $result['uid'];

        if ($event->isRecurrent()) {
            $result['rrule'] = $event->getRecurrenceRule();
            $result['recurrence_id'] = $event->getRecurrenceId();

            // Append RECURRENCE-ID to generated id
            $result['id'] .= '@' . $result['recurrence_id'];
        }

        // TODO reminders

        return $result;
    }
}
