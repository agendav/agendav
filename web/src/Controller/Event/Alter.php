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
use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\DateHelper;
use AgenDAV\CalDAV\Client;
use AgenDAV\EventInstance;
use AgenDAV\Event\RecurrenceId;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class Alter extends JSONController
{

    /**
     * Validates user input
     *
     * @param Symfony\Component\HttpFoundation\ParameterBag $input
     * @return bool
     */
    protected function validateInput(ParameterBag $input)
    {
        $fields = [
            'calendar',
            'timezone',
            'uid',
        ];

        foreach ($fields as $name) {
            if (empty($input->get($name))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Executes this operation
     */
    public function execute(ParameterBag $input, Application $app)
    {
        $timezone = new \DateTimeZone($input->get('timezone'));
        $calendar = $this->client->getCalendarByUrl($input->get('calendar'));
        $resource = $this->client->fetchObjectByUid($calendar, $input->get('uid'));

        $recurrence_id = null;

        if (!empty($input->get('recurrence_id'))) {
            $recurrence_id = RecurrenceId::buildFromString($input->get('recurrence_id'));
        }

        $event = $resource->getEvent();
        $instance = $event->getEventInstance($recurrence_id);

        if ($instance === null) {
            throw new \UnexpectedValueException('Empty VCALENDAR?');
        }

        $minutes = $input->getInt('delta');

        // Run specific operation on this event instance
        $this->modifyInstance($instance, $timezone, $minutes, $input);

        $instance->touch();

        $event->storeInstance($instance);
        $resource->setEvent($event);
        $response = $this->client->uploadCalendarObject($resource);

        return $this->generateSuccess([
            'etag' => $response->getHeaderLine('ETag'),
        ]);
    }

    /**
     * Changes the instance to reflect an event resize
     *
     * @param \AgenDAV\EventInstance $instance
     * @param \DateTimeZone $timezone
     * @param int $minutes
     * @param ParameterBag $input
     */
    abstract protected function modifyInstance(
        EventInstance $instance,
        \DateTimeZone $timezone,
        $minutes,
        ParameterBag $input
    );
}
