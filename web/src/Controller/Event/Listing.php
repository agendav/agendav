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

use AgenDAV\Controller\JSONController;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\DateHelper;
use AgenDAV\Event\FullCalendarEvent;
use AgenDAV\Data\Transformer\FullCalendarEventTransformer;
use AgenDAV\Data\Serializer\PlainSerializer;;
use League\Fractal\Resource\Collection;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;

class Listing extends JSONController
{
    /** @var \DateTimeZone */
    private $utc;

    public function __construct()
    {
        parent::__construct();
        $this->utc = new \DateTimeZone('UTC');
        $this->method = 'GET';
    }

    /**
     * Validates user input
     *
     * @param array $input
     * @return bool
     */
    protected function validateInput(array $input)
    {
        $fields = [
            'calendar',
            'timezone',
            'start',
            'end',
        ];

        foreach ($fields as $name) {
            if (empty($input[$name])) {
                return false;
            }
        }

        return true;
    }

    public function execute(array $input, Application $app)
    {
        $calendar = new Calendar($input['calendar']);
        $timezone = new \DateTimeZone($input['timezone']);
        $start = DateHelper::fullcalendarToDateTime($input['start'], $timezone);
        $end = DateHelper::fullcalendarToDateTime($input['end'], $timezone);

        // Optional UID filter
        if (isset($input['uid'])) {
            $uid = $input['uid'];
        }

        // These are needed to query the server
        $start_string = $this->getTimeFilterDatestring($start);
        $end_string = $this->getTimeFilterDatestring($end);


        $execution_fetch_start = microtime(true);

        if (!isset($uid)) {
            $objects = $this->client->fetchObjectsOnCalendar($calendar, $start_string, $end_string);
        } else {
            $object = $this->client->fetchObjectByUid($calendar, $uid);
            $objects = array($object);
        }

        $execution_fetch_end = microtime(true);

        $execution_parse_start = microtime(true);
        $fullcalendar_events = $this->buildFullCalendarEvents($calendar, $objects, $start, $end);
        $execution_parse_end = microtime(true);

        $this->addPerformanceHeaders(
            $execution_fetch_end - $execution_fetch_start,
            $execution_parse_end - $execution_parse_start
        );

        return $this->serializeFullCalendarEvents($fullcalendar_events, $timezone, $app);
    }


    /**
     * Generates a string suitable for a timefilter for querying the calendar
     *
     * @param \DateTimeImmutable $datetime
     * @return string
     */
    protected function getTimeFilterDatestring(\DateTimeImmutable $datetime)
    {
        $datetime_utc = $datetime->setTimeZone($this->utc);

        return $datetime_utc->format('Ymd\THis\Z');
    }

    protected function buildFullCalendarEvents(
        Calendar $calendar,
        array $objects,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    )
    {
        $result = [];

        foreach ($objects as $object) {
            $master_event = $object->getEvent();
            $instances = $master_event->expand($start, $end);
            $fullcalendar_events = FullCalendarEvent::generateFrom(
                $object,
                $calendar,
                $instances
            );
            $result = array_merge($result, $fullcalendar_events);
        }

        return $result;
    }

    /**
     * Serialize a list of FullCalendar events using Fractal
     *
     * @param array $events FullCalendar events
     * @param \DateTimeZone $timezone Time zone the user has
     * @param \Silex\Application $app
     * @return array
     */
    protected function serializeFullCalendarEvents(array $events, \DateTimeZone $timezone, Application $app)
    {
        $fractal = $app['fractal'];
        $fractal->setSerializer(new PlainSerializer);
        $transformer = new FullCalendarEventTransformer($timezone);
        $collection = new Collection($events, $transformer);

        return new JsonResponse($fractal->createData($collection)->toArray());
    }

    /**
     * Adds performance headers, that contain total fetch time and total parse time
     *
     * @param int $execution_fetch
     * @param int $execution_parse
     */
    protected function addPerformanceHeaders($execution_fetch, $execution_parse)
    {
        $total_fetch = sprintf('%.4F', $execution_fetch);
        $total_parse = sprintf('%.4F', $execution_parse);

        $this->addHeader("X-Fetch-Time", $total_fetch);
        $this->addHeader("X-Parse-Time", $total_parse);
    }

}
