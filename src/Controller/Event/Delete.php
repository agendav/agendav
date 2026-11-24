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
use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\Event\RecurrenceId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class Delete extends JSONController
{
    protected function validateInput(ParameterBag $input)
    {
        return $this->hasStrings($input, ['calendar', 'uid', 'href', 'etag'])
            && $this->isOptionalRecurrenceId($input->get('recurrence_id'));
    }

    protected function execute(
        ParameterBag $input,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $calendar = $this->client->getCalendarByUrl($input->get('calendar'));

        if (!$calendar->isWritable()) {
            $translator = $this->container->get('translator');
            return $this->generateException($response, $translator->trans('messages.error_calendar_readonly'), 403);
        }

        $uid = $input->get('uid');
        $object = $this->client->fetchObjectByUid($calendar, $uid);
        $object->setEtag($input->get('etag'));

        if (!empty($input->get('recurrence_id'))) {
            return $this->removeInstance($object, $input->get('recurrence_id'), $response);
        }

        return $this->removeObject($object, $response);
    }

    protected function removeObject(CalendarObject $object, ResponseInterface $response): ResponseInterface
    {
        $this->client->deleteCalendarObject($object);
        return $this->generateSuccess($response);
    }

    protected function removeInstance(
        CalendarObject $object,
        string $recurrence_id_string,
        ResponseInterface $response
    ): ResponseInterface {
        $recurrence_id = RecurrenceId::buildFromString($recurrence_id_string);

        $event = $object->getEvent();
        $event->removeInstance($recurrence_id);
        $object->setEvent($event);

        $caldavResponse = $this->client->uploadCalendarObject($object);

        return $this->generateSuccess($response, [
            'etag' => $caldavResponse->getHeaderLine('ETag'),
        ]);
    }
}
