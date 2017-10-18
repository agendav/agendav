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

use AgenDAV\Uuid;
use AgenDAV\DateHelper;
use AgenDAV\Controller\JSONController;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\Data\Transformer\CalendarTransformer;
use League\Fractal\Resource\Collection;
use Silex\Application;
use Symfony\Component\HttpFoundation\ParameterBag;

class Save extends JSONController
{
    /** @var \AgenDAV\Event\Builder */
    protected $builder;

    /**
     * Validates user input
     *
     * @param \Symfony\Component\HttpFoundation\ParameterBag $input
     * @return bool
     */
    protected function validateInput(ParameterBag $input)
    {
        $fields = [
            'calendar',
            'summary',
            'timezone',
            'start',
            'end',
        ];

        if ($this->isModification($input)) {
            $fields[] = 'etag';
            $fields[] = 'original_calendar';
        }

        foreach ($fields as $name) {
            if (empty($input->get($name))) {
                return false;
            }
        }

        // Check if end >= start
        $start = DateHelper::frontEndToDateTime($input->get('start'), new \DateTimeZone('UTC'));
        $end = DateHelper::frontEndToDateTime($input->get('end'), new \DateTimeZone('UTC'));

        if ($end < $start) {
            return false;
        }


        return true;
    }

    public function execute(ParameterBag $input, Application $app)
    {
        $this->builder = $app['event.builder'];
        if ($this->isModification($input)) {
            return $this->modifyObject($input);
        }

        return $this->createObject($input);
    }

    /**
     * Decides whether this request tries to create a new event or update
     * an existing one
     *
     * @param \Symfony\Component\HttpFoundation\ParameterBag $input
     * @return bool
     */
    protected function isModification(ParameterBag $input)
    {
        return !empty($input->get('uid'));
    }

    protected function createObject(ParameterBag $input)
    {
        $calendar = $this->client->getCalendarByUrl($input->get('calendar'));

        // Create a new CalendarObject inside $calendar
        $uid = Uuid::generate();
        $object = CalendarObject::generateOnCalendar($calendar, $uid);
        $event = $this->builder->createEvent($uid);

        $instance = $this->builder->createEventInstanceWithInput($event, $input->all());

        $event->storeInstance($instance);
        $object->setEvent($event);
        $this->client->uploadCalendarObject($object);

        // Frontend expects us to return the list of affected calendars
        return $this->generateSuccess([ $input->get('calendar') ]);
    }

    protected function modifyObject(ParameterBag $input)
    {
        $source_calendar = $this->client->getCalendarByUrl(
            $input->get('original_calendar')
        );
        $destination_calendar = $this->client->getCalendarByUrl($input->get('calendar'));

        // Fetch current event to apply modifications on top of it
        $uid = $input->get('uid');
        $source_object = $this->client->fetchObjectByUid($source_calendar, $uid);
        $event = $source_object->getEvent();

        $instance = $this->builder->createEventInstanceWithInput($event, $input->all());
        $event->storeInstance($instance);

        $object = CalendarObject::generateOnCalendar($destination_calendar, $uid);
        $object->setEtag($input->get('etag'));
        $object->setEvent($event);

        // New object, so don't overwrite existing objects
        if ($source_calendar->getUrl() !== $destination_calendar->getUrl()) {
            $object->setEtag(null);
        }

        $this->client->uploadCalendarObject($object);

        if ($source_calendar->getUrl() !== $destination_calendar->getUrl()) {
            $this->client->deleteCalendarObject($source_object);

            return $this->generateSuccess([
                $input->get('original_calendar'),
                $input->get('calendar')
            ]);
        }

        // Frontend expects us to return the list of affected calendars
        return $this->generateSuccess([ $input->get('calendar') ]);
    }
}
