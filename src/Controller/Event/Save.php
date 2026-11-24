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
use AgenDAV\Data\Reminder;
use AgenDAV\EventInstance;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Recur\RRuleIterator;
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

        if (!$this->hasStrings($input, $fields)
            || !$this->hasOptionalStrings($input, ['uid', 'location', 'description', 'color', 'rrule'])
            || !$this->hasOptionalBools($input, ['allday'])
            || !$this->isTimezone($input->get('timezone'))
            || !$this->isOptionalRecurrenceId($input->get('recurrence_id'))
            || !$this->isOptionalOneOf($input->get('class'), EventInstance::CLASSIFICATIONS)
            || !$this->isOptionalOneOf($input->get('transp'), EventInstance::TRANSPARENCIES)
            || !$this->isOptionalColor($input->get('color'))
            || !$this->isOptionalRepeatRule($input->get('rrule'))
            || !$this->isOptionalReminders($input->get('reminders'))
        ) {
            return false;
        }

        $start = $this->parseEventDate($input->get('start'));
        $end = $this->parseEventDate($input->get('end'));

        return $start !== null && $end !== null && $end >= $start;
    }

    /**
    * @return \DateTimeImmutable|null null if the value is no ISO 8601 UTC date like 2026-11-23T10:00:00.000Z
    */
    private function parseEventDate(string $value): ?\DateTimeImmutable
    {
        try {
            return DateHelper::frontEndToDateTime($value);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }

    private function isOptionalOneOf(mixed $value, array $allowed): bool
    {
        return $value === null || in_array($value, $allowed, true);
    }

    /**
    * An empty color removes the event color
    */
    private function isOptionalColor(mixed $value): bool
    {
        return $value === null || $value === '' || $this->isColor($value);
    }

    /**
    * An empty rule removes the repetition. Invalid rules would break the
    * event listing of the whole calendar, so they are parsed here.
    */
    private function isOptionalRepeatRule(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        if (strncasecmp($value, 'RRULE:', 6) === 0) {
            $value = substr($value, 6);
        }
        try {
            new RRuleIterator($value, new \DateTimeImmutable());
        } catch (InvalidDataException $exception) {
            return false;
        }
        return true;
    }

    /**
    * Reminders are optional. If set, "count" (numbers) and "unit"
    * (see Reminder::UNITS) are lists of the same length.
    */
    private function isOptionalReminders(mixed $reminders): bool
    {
        if ($reminders === null) {
            return true;
        }
        if (!is_array($reminders)) {
            return false;
        }
        $counts = $reminders['count'] ?? [];
        $units = $reminders['unit'] ?? [];
        if (!is_array($counts) || !is_array($units)
            || !array_is_list($counts) || !array_is_list($units)
            || count($counts) !== count($units)
        ) {
            return false;
        }
        foreach ($counts as $i => $count) {
            if ($this->parseInt($count) === null || !in_array($units[$i], Reminder::UNITS, true)) {
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

        if (!$calendar->isWritable()) {
            $translator = $this->container->get('translator');
            return $this->generateException($response, $translator->trans('messages.error_calendar_readonly'), 403);
        }

        $uid = Uuid::generate();
        $object = CalendarObject::generateOnCalendar($calendar, $uid);
        $event = $this->builder->createEvent($uid);

        $instance = $this->builder->createEventInstanceWithInput($event, $input->all());

        $event->storeInstance($instance);
        $object->setEvent($event);
        $this->client->uploadCalendarObject($object);

        return $this->generateSuccess($response, ['calendars' => [$input->get('calendar')]]);
    }

    protected function modifyObject(ParameterBag $input, ResponseInterface $response): ResponseInterface
    {
        $source_calendar = $this->client->getCalendarByUrl($input->get('original_calendar'));
        $destination_calendar = $this->client->getCalendarByUrl($input->get('calendar'));

        if (!$source_calendar->isWritable() || !$destination_calendar->isWritable()) {
            $translator = $this->container->get('translator');
            return $this->generateException($response, $translator->trans('messages.error_calendar_readonly'), 403);
        }

        $uid = $input->get('uid');
        $source_object = $this->client->fetchObjectByUid($source_calendar, $uid);
        $event = $source_object->getEvent();

        $instance = $this->builder->createEventInstanceWithInput($event, $input->all());
        $event->storeInstance($instance);

        $moving = $source_calendar->getUrl() !== $destination_calendar->getUrl();

        if ($moving) {
            $object = CalendarObject::generateOnCalendar($destination_calendar, $uid);
            $object->setEtag(null);
        } else {
            // Reuse the fetched object so the real server URL is used, not a generated one.
            // This avoids a 412 when the file is not named after the UID.
            $object = $source_object;
            $object->setEtag($input->get('etag'));
        }

        $object->setEvent($event);
        $this->client->uploadCalendarObject($object);

        if ($moving) {
            $this->client->deleteCalendarObject($source_object);
            return $this->generateSuccess($response, [
                'calendars' => [$input->get('original_calendar'), $input->get('calendar')],
            ]);
        }

        return $this->generateSuccess($response, ['calendars' => [$input->get('calendar')]]);
    }
}
