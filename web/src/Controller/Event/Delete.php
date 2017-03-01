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
use AgenDAV\Event\RecurrenceId;
use Silex\Application;
use Symfony\Component\HttpFoundation\ParameterBag;

class Delete extends JSONController
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
            'uid',
            'href',
            'etag',
        ];

        foreach ($fields as $name) {
            if (empty($input->get($name))) {
                return false;
            }
        }

        return true;
    }

    public function execute(ParameterBag $input, Application $app)
    {
        // Load calendar object
        $calendar = $this->client->getCalendarByUrl($input->get('calendar'));
        $uid = $input->get('uid');
        $object = $this->client->fetchObjectByUid($calendar, $uid);
        $object->setEtag($input->get('etag'));

        // Single instance removal. We need the full object
        if (!empty($input->get('recurrence_id'))) {
            return $this->removeInstance($object, $input->get('recurrence_id'));
        }

        return $this->removeObject($object);
    }

    /**
     * Completely removes an object from the server
     *
     * @param \AgenDAV\CalDAV\Resource\CalendarObject
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function removeObject(CalendarObject $object)
    {
        $this->client->deleteCalendarObject($object);
        return $this->generateSuccess();
    }

    /**
     * Remove an event instance
     *
     * @param \AgenDAV\CalDAV\Resource\CalendarObject
     * @param string $recurrence_id_string
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function removeInstance(CalendarObject $object, $recurrence_id_string)
    {
        $recurrence_id = RecurrenceId::buildFromString($recurrence_id_string);

        $event = $object->getEvent();
        $event->removeInstance($recurrence_id);
        $object->setEvent($event);

        $this->client->uploadCalendarObject($object);
        return $this->generateSuccess();
    }

}
