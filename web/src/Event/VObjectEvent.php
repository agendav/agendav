<?php

namespace AgenDAV\Event;

/*
 * Copyright 2014 Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\Event;
use AgenDAV\EventInstance;
use AgenDAV\Event\VObjectEventInstance;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * VObject implementation of Events
 *
 */
class VObjectEvent implements Event
{
    protected $vcalendar;

    protected $is_recurrent;

    protected $exceptions;

    protected $uid;

    /**
     * @param mixed VCalendar $vcalendar
     */
    public function __construct(VCalendar $vcalendar)
    {
        $this->vcalendar = $vcalendar;
        $this->is_recurrent = $this->checkIfRecurrent();
        $this->exceptions = [];

        if ($this->is_recurrent) {
            $this->exceptions = $this->findRecurrenceExceptions($vcalendar);
        }

        $this->uid = $this->findUid();
    }

    public function isRecurrent()
    {
        return $this->is_recurrent;
    }

    public function getUid()
    {
        return $this->uid;
    }

    public function setUid($uid)
    {
        if ($this->uid !== null) {
            throw new \LogicException('Existing uids cannot be changed');
        }

        $this->uid = $uid;
    }

    public function expand(\DateTime $start, \DateTime $end)
    {
        $expanded_vcalendar = clone $this->vcalendar;
        $expanded_vcalendar->expand($start, $end);

        $result = [];
        $rrule = null;

        if ($this->isRecurrent()) {
            $rrule = $this->vcalendar->VEVENT[0]->RRULE;
        }

        foreach ($expanded_vcalendar->VEVENT as $vevent) {
            if ($rrule !== null) {
                $vevent->RRULE = $rrule;
            }

            $result[] = new VObjectEventInstance($vevent);
        }

        return $result;
    }

    public function isException($recurrence_id)
    {
        return isset($this->exceptions[$recurrence_id]);
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->vcalendar->serialize();
    }

    /**
     * Creates a new VObjectInstance for this event
     *
     * @return \AgenDAV\Event\VObjectEventInstance
     * @throws \LogicException If $event has no UID assigned
     */
    public function createEventInstance()
    {
        if ($this->uid === null) {
            throw new \LogicException('Event has not been assigned a UID yet!');
        }

        $vevent = $this->vcalendar->create('VEVENT');
        $vevent->UID = $this->uid;

        return new VObjectEventInstance($vevent);
    }


    /**
     * Adds an event instance to this event
     */
    public function addEventInstance(EventInstance $instance)
    {
        // Check if UID matches
        if ($instance->getUid() !== $this->getUid()) {
            throw new \InvalidArgumentException('Event instance UID and self do not match');
        }

        // VObject sets a RECURRENCE-ID when expanding, so let's see if
        // this is a result of expanding or an actual recurrence exception
        $recurrence_id = $instance->getRecurrenceId();
        if ($this->isException($recurrence_id)) {
            // Not supported
            throw new \Exception('Recurrent events modification is not supported');
        }

        $instance->removeRecurrenceId();
        $vevent = $instance->getInternalVEvent();

        $base = $this->vcalendar->getBaseComponent('VEVENT');
        if ($base === null) {
            $this->vcalendar->add($vevent);
        } else {
            $this->vcalendar->VEVENT = $vevent;
        }
    }

    protected function checkIfRecurrent()
    {
        $count = count($this->vcalendar->VEVENT);

        if ($count > 1) {
            return true;
        }

        $vevent_0 = $this->vcalendar->VEVENT[0];

        if (isset($vevent_0->RRULE)) {
            return true;
        }

        return false;
    }

    protected function findRecurrenceExceptions(VCalendar $vcalendar)
    {
        $result = [];
        foreach ($vcalendar->VEVENT as $vevent) {
            $recurrence_id = $vevent->{'RECURRENCE-ID'};
            if ($recurrence_id !== null) {
                $recurrence_id = (string)$recurrence_id;
                $result[$recurrence_id] = true;
            }
        }

        return $result;
    }

    protected function findUid()
    {
        $base_component = $this->vcalendar->getBaseComponent('VEVENT');

        if ($base_component === null) {
            return null;
        }

        return $base_component->UID;
    }
}

