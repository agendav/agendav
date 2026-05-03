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
use AgenDAV\CalDAV\Resource\CalendarObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class Save extends JSONController
{
    /** @var \AgenDAV\Event\Builder */
    protected $builder;

    protected function validateInput(ParameterBag $input)
    {
        $fields = ['calendar', 'summary', 'timezone', 'start', 'end'];

        if ($this->isModification($input)) {
            $fields[] = 'etag';
            $fields[] = 'original_calendar';
        }

        foreach ($fields as $name) {
            if (empty($input->get($name))) {
                return false;
            }
        }

        $start = DateHelper::frontEndToDateTime($input->get('start'), new \DateTimeZone('UTC'));
        $end = DateHelper::frontEndToDateTime($input->get('end'), new \DateTimeZone('UTC'));

        return $end >= $start;
    }

    protected function execute(
        ParameterBag $input,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $this->builder = $this->container->get('event.builder');
        if ($this->isModification($input)) {
            return $this->modifyObject($input, $response);
        }
        return $this->createObject($input, $response);
    }

    protected function isModification(ParameterBag $input): bool
    {
        return !empty($input->get('uid'));
    }

    protected function createObject(ParameterBag $input, ResponseInterface $response): ResponseInterface
    {
        $calendar = $this->client->getCalendarByUrl($input->get('calendar'));

        $uid = Uuid::generate();
        $object = CalendarObject::generateOnCalendar($calendar, $uid);
        $event = $this->builder->createEvent($uid);

        $instance = $this->builder->createEventInstanceWithInput($event, $input->all());

        $event->storeInstance($instance);
        $object->setEvent($event);
        $this->client->uploadCalendarObject($object);

        return $this->generateSuccess($response, [$input->get('calendar')]);
    }

    protected function modifyObject(ParameterBag $input, ResponseInterface $response): ResponseInterface
    {
        $source_calendar = $this->client->getCalendarByUrl($input->get('original_calendar'));
        $destination_calendar = $this->client->getCalendarByUrl($input->get('calendar'));

        $uid = $input->get('uid');
        $source_object = $this->client->fetchObjectByUid($source_calendar, $uid);
        $event = $source_object->getEvent();

        $instance = $this->builder->createEventInstanceWithInput($event, $input->all());
        $event->storeInstance($instance);

        $object = CalendarObject::generateOnCalendar($destination_calendar, $uid);
        $object->setEtag($input->get('etag'));
        $object->setEvent($event);

        if ($source_calendar->getUrl() !== $destination_calendar->getUrl()) {
            $object->setEtag(null);
        }

        $this->client->uploadCalendarObject($object);

        if ($source_calendar->getUrl() !== $destination_calendar->getUrl()) {
            $this->client->deleteCalendarObject($source_object);
            return $this->generateSuccess($response, [
                $input->get('original_calendar'),
                $input->get('calendar'),
            ]);
        }

        return $this->generateSuccess($response, [$input->get('calendar')]);
    }
}
