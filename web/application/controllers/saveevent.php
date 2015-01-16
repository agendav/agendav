<?php

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

use AgenDAV\Uuid;
use AgenDAV\JSONController;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\Data\Transformer\CalendarTransformer;
use League\Fractal\Resource\Collection;

class Saveevent extends JSONController
{
    /** @var \AgenDAV\Event\Builder */
    protected $builder;

    public function __construct()
    {
        parent::__construct();
        $this->builder = $this->container['event_builder'];
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
            'summary',
            'timezone',
        ];

        if ($this->isModification($input)) {
            $fields[] = 'etag';
            $fields[] = 'original_calendar';
        }

        foreach ($fields as $name) {
            if (empty($input[$name])) {
                return false;
            }
        }


        return true;
    }

    public function execute(array $input)
    {
        if ($this->isModification($input)) {
            return $this->modifyObject($input);
        }

        return $this->createObject($input);
    }

    /**
     * Decides whether this request tries to create a new event or update
     * an existing one
     *
     * @param array $input
     * @return bool
     */
    protected function isModification(array $input)
    {
        return !empty($input['uid']);
    }

    protected function createObject($input)
    {
        $calendar = $this->client->getCalendarByUrl($input['calendar']);

        // Create a new CalendarObject inside $calendar
        $uid = Uuid::generate();
        $object = CalendarObject::generateOnCalendar($calendar, $uid);
        $event = $this->builder->createEvent($uid);

        // TODO work with recurrence-ids
        $instance = $this->builder->createEventInstanceWithInput($event, $input);

        $event->setBaseEventInstance($instance);
        $object->setEvent($event);
        $this->client->uploadCalendarObject($object);

        // Frontend expects us to return the list of affected calendars
        return $this->generateSuccess([ $input['calendar'] ]);
    }

    protected function modifyObject($input)
    {
        $source_calendar = $this->client->getCalendarByUrl($input['original_calendar']);
        $destination_calendar = $this->client->getCalendarByUrl($input['calendar']);

        // Fetch current event to apply modifications on top of it
        $uid = $input['uid'];
        $source_object = $this->client->fetchObjectByUid($source_calendar, $uid);
        $event = $source_object->getEvent();

        // TODO work with recurrence-ids
        $instance = $this->builder->createEventInstanceWithInput($event, $input);
        $event->setBaseEventInstance($instance);

        $object = CalendarObject::generateOnCalendar($destination_calendar, $uid);
        $object->setEtag($input['etag']);
        $object->setEvent($event);

        // New object, so don't overwrite existing objects
        if ($source_calendar->getUrl() !== $destination_calendar->getUrl()) {
            $object->setEtag(null);
        }

        $this->client->uploadCalendarObject($object);

        if ($source_calendar->getUrl() !== $destination_calendar->getUrl()) {
            $this->client->deleteCalendarObject($source_object);

            return $this->generateSuccess([
                $input['original_calendar'], $input['calendar']
            ]);
        }

        // Frontend expects us to return the list of affected calendars
        return $this->generateSuccess([ $input['calendar'] ]);
    }
}
