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
use AgenDAV\EventInstance;
use AgenDAV\Event\RecurrenceId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class Alter extends JSONController
{
    protected function validateInput(ParameterBag $input)
    {
        // delta: minutes, may be negative
        return $this->hasStrings($input, ['calendar', 'timezone', 'uid'])
            && $this->isTimezone($input->get('timezone'))
            && $this->isOptionalRecurrenceId($input->get('recurrence_id'))
            && filter_var($input->get('delta'), FILTER_VALIDATE_INT) !== false;
    }

    protected function execute(
        ParameterBag $input,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $timezone = new \DateTimeZone($input->get('timezone'));
        $calendar = $this->client->getCalendarByUrl($input->get('calendar'));

        if (!$calendar->isWritable()) {
            $translator = $this->container->get('translator');
            return $this->generateException($response, $translator->trans('messages.error_calendar_readonly'), 403);
        }

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

        $this->modifyInstance($instance, $timezone, $minutes, $input);

        $instance->touch();
        $event->storeInstance($instance);
        $resource->setEvent($event);
        $caldavResponse = $this->client->uploadCalendarObject($resource);

        return $this->generateSuccess($response, [
            'etag' => $caldavResponse->getHeaderLine('ETag'),
        ]);
    }

    abstract protected function modifyInstance(
        EventInstance $instance,
        \DateTimeZone $timezone,
        $minutes,
        ParameterBag $input
    );
}
