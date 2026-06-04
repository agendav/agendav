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

use AgenDAV\Controller\JSONController;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\DateHelper;
use AgenDAV\Event\FullCalendarEvent;
use AgenDAV\Data\Transformer\FullCalendarEventTransformer;
use AgenDAV\Data\Serializer\PlainSerializer;
use League\Fractal\Resource\Collection;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class Listing extends JSONController
{
    protected $method = 'GET';

    /** @var \DateTimeZone */
    private $utc;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->utc = new \DateTimeZone('UTC');
    }

    protected function validateInput(ParameterBag $input)
    {
        foreach (['calendar', 'timezone', 'start', 'end'] as $name) {
            if (empty($input->get($name))) {
                return false;
            }
        }
        return true;
    }

    protected function execute(
        ParameterBag $input,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $calendar = new Calendar($input->get('calendar'));
        if ($input->getBoolean('is_subscribed') === true) {
            $calendar->setSubscribed(true);
        }
        $timezone = new \DateTimeZone($input->get('timezone'));
        $start = DateHelper::fullcalendarToDateTime($input->get('start'), $timezone);
        $end = DateHelper::fullcalendarToDateTime($input->get('end'), $timezone);

        $start_string = $this->getTimeFilterDatestring($start);
        $end_string = $this->getTimeFilterDatestring($end);

        $execution_fetch_start = microtime(true);

        if ($calendar->isSubscribed()) {
            $objects = $this->client->fetchObjectsOnSubscribedCalendar($calendar);
        } elseif (!$input->has('uid')) {
            $objects = $this->client->fetchObjectsOnCalendar($calendar, $start_string, $end_string);
        } else {
            $object = $this->client->fetchObjectByUid($calendar, $input->get('uid'));
            $objects = [$object];
        }

        $execution_fetch_end = microtime(true);

        $execution_parse_start = microtime(true);
        $fullcalendar_events = $this->buildFullCalendarEvents($calendar, $objects, $start, $end);
        $execution_parse_end = microtime(true);

        $this->addPerformanceHeaders(
            $execution_fetch_end - $execution_fetch_start,
            $execution_parse_end - $execution_parse_start
        );

        return $this->serializeFullCalendarEvents($fullcalendar_events, $timezone, $response);
    }

    protected function getTimeFilterDatestring(\DateTimeImmutable $datetime)
    {
        return $datetime->setTimezone($this->utc)->format('Ymd\THis\Z');
    }

    protected function buildFullCalendarEvents(
        Calendar $calendar,
        array $objects,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ) {
        $result = [];
        foreach ($objects as $object) {
            $master_event = $object->getEvent();
            $instances = $master_event->expand($start, $end);
            $fullcalendar_events = FullCalendarEvent::generateFrom($object, $calendar, $instances);
            $result = array_merge($result, $fullcalendar_events);
        }
        return $result;
    }

    protected function serializeFullCalendarEvents(
        array $events,
        \DateTimeZone $timezone,
        ResponseInterface $response
    ): ResponseInterface {
        $fractal = $this->container->get('fractal');
        $fractal->setSerializer(new PlainSerializer());
        $transformer = new FullCalendarEventTransformer($timezone);
        $collection = new Collection($events, $transformer);

        return $this->jsonResponse($response, $fractal->createData($collection)->toArray(), 200);
    }

    protected function addPerformanceHeaders($execution_fetch, $execution_parse)
    {
        $this->addHeader('X-Fetch-Time', sprintf('%.4F', $execution_fetch));
        $this->addHeader('X-Parse-Time', sprintf('%.4F', $execution_parse));
    }
}
