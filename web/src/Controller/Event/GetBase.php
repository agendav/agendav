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

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\Event\FullCalendarEvent;
use AgenDAV\Data\Transformer\FullCalendarEventTransformer;
use AgenDAV\Data\Serializer\PlainSerializer;
use League\Fractal\Resource\Item;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class GetBase extends Listing
{
    protected function validateInput(ParameterBag $input)
    {
        foreach (['calendar', 'uid', 'timezone'] as $name) {
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
        $timezone = new \DateTimeZone($input->get('timezone'));
        $uid = $input->get('uid');

        $execution_fetch_start = microtime(true);
        $object = $this->client->fetchObjectByUid($calendar, $uid);
        $execution_fetch_end = microtime(true);

        $execution_parse_start = microtime(true);
        $fullcalendar_event = $this->buildFullCalendarEvent($calendar, $object);
        $execution_parse_end = microtime(true);

        $this->addPerformanceHeaders(
            $execution_fetch_end - $execution_fetch_start,
            $execution_parse_end - $execution_parse_start
        );

        return $this->serializeEvent($fullcalendar_event, $timezone, $response);
    }

    protected function buildFullCalendarEvent(Calendar $calendar, CalendarObject $object)
    {
        $master_event = $object->getEvent();
        $base_instance = [$master_event->getEventInstance()];
        $fullcalendar_events = FullCalendarEvent::generateFrom($object, $calendar, $base_instance);
        return current($fullcalendar_events);
    }

    protected function serializeEvent(
        FullCalendarEvent $event,
        \DateTimeZone $timezone,
        ResponseInterface $response
    ): ResponseInterface {
        $fractal = $this->container->get('fractal');
        $fractal->setSerializer(new PlainSerializer());
        $transformer = new FullCalendarEventTransformer($timezone);
        $item = new Item($event, $transformer);

        return $this->jsonResponse($response, $fractal->createData($item)->toArray(), 200);
    }
}
