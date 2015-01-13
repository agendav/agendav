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
use AgenDAV\EventInstance;

abstract class Alter
{
    /** @var AgenDAV\CalDAV\Client */
    protected $client;


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

        // Run specific operation on this event instance
        $this->modifyInstance($instance, $timezone, $minutes, $input);

        $instance->touch();

        $event->setBaseEventInstance($instance);
        $resource->setEvent($event);
        $response = $this->client->uploadCalendarObject($resource);

        return [
            'etag' => $response->getHeader('ETag'),
        ];
    }

    /**
     * Changes the instance to reflect an event resize
     *
     * @param \AgenDAV\EventInstance $instance
     * @param \DateTimeZone $timezone
     * @param int $minutes
     * @param Array $input
     */
    abstract protected function modifyInstance(
        EventInstance $instance,
        \DateTimeZone $timezone,
        $minutes,
        array $input = []
    );
}
