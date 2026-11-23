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
use AgenDAV\Data\Helper\Color;
use AgenDAV\EventInstance;
use AgenDAV\Event\RecurrenceId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class Alter extends JSONController
{
    protected function validateInput(ParameterBag $input)
    {
        foreach (['calendar', 'timezone', 'uid'] as $name) {
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
        $timezone = new \DateTimeZone($input->get('timezone'));
        $calendar = $this->client->getCalendarByUrl($input->get('calendar'));

        if (!$calendar->isWritable()) {
            $translator = $this->container->get('translator');
            return $this->generateError($response, $translator->trans('messages.error_calendar_readonly'), 403);
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

        if ($this->container->get('calendar.event_color_format') === 'css3') {
            $instance->setColor(Color::toName($instance->getColor()));
        }

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
