<?php

namespace AgenDAV\Operation\Event;

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

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\DateHelper;
use AgenDAV\CalDAV\Client;
use AgenDAV\Log;
use AgenDAV\EventInstance;

class Alter
{
    /** @var AgenDAV\CalDAV\Client */
    protected $client;

    /*
     * Event drop possibilities
     */
    const TIMED_TO_TIMED = 0;
    const TIMED_TO_ALLDAY = 1;
    const ALLDAY_TO_TIMED = 2;
    const ALLDAY_TO_ALLDAY = 3;

    const ALLDAY_TYPE = 1;

    /**
     * Builds a new event alter handler
     *
     * @param AgenDAV\CalDAV\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Executes this operation
     *
     * Receives an input array containing the following keys:
     *
     *  - uid
     *  - calendar
     *  - timezone
     *  - etag
     *  - delta
     *  - allday
     *  - was_allday
     *  - type
     *
     *  @param Array $input
     *  @result Array
     */
    public function execute(Array $input)
    {
        $timezone = new \DateTimeZone($input['timezone']);
        $calendar = $this->client->getCalendarByUrl($input['calendar']);
        $resource = $this->client->fetchObjectByUid($calendar, $input['uid']);

        $event = $resource->getEvent();
        $instance = $event->getEventInstance(); // TODO: recurrence-id?

        if ($instance === null) {
            throw new \UnexpectedValueException('Empty VCALENDAR?');
        }

        $minutes = (int) $input['delta'];

        $type = $input['type'];

        log_message('INTERNALS', 'Altering resource ' . $resource->getUrl() . ', type ' . $type . ', delta=' . $minutes);

        if ($type === 'drop') {
            $movement = $this->describeMovement($input['was_allday'], $input['allday']);
            log_message('INTERNALS', 'Resolved that movement = ' . $movement);
            $this->dropEvent($instance, $timezone, $minutes, $movement);
        }

        if ($type === 'resize') {
            $this->resizeEvent($instance, $minutes);
        }

        $instance->touch();

        $event->setBaseEventInstance($instance);
        $resource->setEvent($event);
        $response = $this->client->uploadCalendarObject($resource);

        return [
            'etag' => $response->getHeader('ETag'),
        ];
    }

    /**
     * Changes the instance to reflect an event drop
     *
     * @param \AgenDAV\EventInstance $instance
     * @param \DateTimeZone $timezone
     * @param int $minutes
     * @param int $movement
     */
    protected function dropEvent(EventInstance $instance, \DateTimeZone $timezone, $minutes, $movement)
    {
        $start = $instance->getStart();
        $end = $instance->getEnd();

        log_message('INTERNALS', 'Original: start='.$start->format('c').', end=' . $end->format('c'));

        if ($movement === self::ALLDAY_TO_ALLDAY) {
            $start->modify($minutes . ' minutes');
            $end->modify($minutes . ' minutes');
        }

        if ($movement === self::ALLDAY_TO_TIMED) {
            $start = DateHelper::createDateTime(
                'YmdHis',
                $start->format('YmdHis'),
                $timezone
            );
            $start->modify($minutes . ' minutes');

            // defaultTimedEventDuration (Fullcalendar) is set to 1h
            $end = clone $start;
            $end->modify('+1 hour');
        }

        if ($movement === self::TIMED_TO_ALLDAY) {
            $start->setTime(0, 0, 0);
            $start = DateHelper::createDateTime(
                'YmdHis',
                $start->format('YmdHis'),
                new \DateTimeZone('UTC')
            );
            $start->modify($minutes . ' minutes');

            // defaultAllDayEventDuration (Fullcalendar) is set to 1 day
            $end = clone $start;
            $end->modify('+1 day');
        }

        if ($movement === self::TIMED_TO_TIMED) {
            $start->modify($minutes . ' minutes');
            $end->modify($minutes . ' minutes');
        }

        // Update start and end on instance, depending on movement
        $allday = ($movement & self::ALLDAY_TYPE) !== 0;
        $instance->setStart($start, $allday);
        $instance->setEnd($end, $allday);

        log_message('INTERNALS', 'Finally: start='.$start->format('c').', end=' . $end->format('c'));
    }

    /**
     * Changes the instance to reflect an event resize
     *
     * @param \AgenDAV\EventInstance $instance
     * @param int $minutes
     */
    protected function resizeEvent(EventInstance $instance, $minutes)
    {
        $start = $instance->getStart();
        $end = $instance->getEnd();

        $end->modify($minutes .' minutes');
        $instance->setStart($start, $instance->isAllDay());
        $instance->setEnd($end, $instance->isAllDay());
    }

    /**
     * Decide which type of movement the user has done. This applies to
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
