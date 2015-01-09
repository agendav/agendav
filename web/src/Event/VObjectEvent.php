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

    protected $repeat_rule;

    protected $exceptions;

    protected $uid;

    /**
     * @param mixed VCalendar $vcalendar
     */
    public function __construct(VCalendar $vcalendar)
    {
        $this->vcalendar = $vcalendar;
        $this->is_recurrent = false;
        $this->exceptions = [];

        $this->repeat_rule = $this->extractRRule();

        if ($this->repeat_rule !== null) {
            $this->is_recurrent = true;
            $this->exceptions = $this->findRecurrenceExceptions($vcalendar);
        }

        $this->uid = $this->findUid();
    }

    public function isRecurrent()
    {
        return $this->is_recurrent;
    }

    public function getRepeatRule()
    {
        return $this->repeat_rule;
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
     * Creates a new EventInstance for this event. If the event already
     * had a base event instance assigned, a copy of it will be returned.
     *
     * If not, a clean event instance will be returned.
     *
     * @return \AgenDAV\Event\VObjectEventInstance
     * @throws \LogicException If current event has no UID assigned
     */
    public function createEventInstance()
    {
        if ($this->uid === null) {
            throw new \LogicException('Event has not been assigned a UID yet!');
        }

        $base = $this->vcalendar->getBaseComponent('VEVENT');
        if ($base === null) {
            $vevent = $this->vcalendar->create('VEVENT');
            $vevent->UID = $this->uid;
        } else {
            $vevent = clone $base;
        }

        if ($this->isRecurrent()) {
            $vevent->RRULE = $this->getRepeatRule();
        }

        return new VObjectEventInstance($vevent);
    }

    /**
     * Sets base EventInstance for this event
     *
     * @param \AgenDAV\EventInstance $instance
     * @throws \InvalidArgumentException If event instance UID does not match
     *                                   current event UID
     */
    public function setBaseEventInstance(EventInstance $instance)
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
        $instance->updateChangeProperties();

        // Add this event instance (case of empty VCALENDAR) or merge
        // with the existing one to avoid existing properties to be lost
        $base = $this->vcalendar->getBaseComponent('VEVENT');
        if ($base === null) {
            $vevent = $instance->getInternalVEvent();
            $this->vcalendar->add($vevent);
        } else {
            $resulting_instance = new VObjectEventInstance($base);
            $resulting_instance->copyPropertiesFrom($instance);
            $vevent = $resulting_instance->getInternalVEvent();
            $this->vcalendar->VEVENT = $vevent;
        }
    }

    /**
     * Extracts the RRULE property from the main VEVENT contained in the
     * VCALENDAR, if any.
     *
     * @return string|null RRULE definition, or null if not found
     */
    protected function extractRRule()
    {

        $base = $this->vcalendar->getBaseComponent();

        if ($base === null) {
            return null;
        }

        if (isset($base->RRULE)) {
            return (string) $base->RRULE;
        }

        return null;
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

        return (string) $base_component->UID;
    }
}

