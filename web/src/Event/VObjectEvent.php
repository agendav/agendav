<?php

namespace AgenDAV\Event;

/*
 * Copyright 2014-2015 Jorge López Pérez <jorge@adobo.org>
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
use AgenDAV\Event\VObjectHelper;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * VObject implementation of Events
 *
 */
class VObjectEvent implements Event
{
    /** @var Sabre\VObject\Component\VCalendar */
    protected $vcalendar;

    /** @var bool */
    protected $is_recurrent;

    /** @var string */
    protected $repeat_rule;

    /** @var string[] */
    protected $exceptions;

    /** @var string */
    protected $uid;

    /**
     * Builds a new VObjectEvent
     *
     * @param Sabre\VObject\Component\VCalendar $vcalendar
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

    /**
     * Checks if current event is recurrent
     *
     * @return bool
     */
    public function isRecurrent()
    {
        return $this->is_recurrent;
    }

    /**
     * Returns the UID for all event instances under this event
     *
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }


    /**
     * Sets UID for this event.
     *
     * @param string $uid
     * @throws \LogicException if this event already has an UID assigned
     */
    public function setUid($uid)
    {
        if ($this->uid !== null) {
            throw new \LogicException('Existing uids cannot be changed');
        }

        $this->uid = $uid;
    }

    /**
     * Returns the RRULE for all event instances under this event
     *
     * @return string
     */
    public function getRepeatRule()
    {
        return $this->repeat_rule;
    }

    /**
     * Gets all event instances for a range of dates. If the event is not
     * recurrent, a single instance will be returned
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @return AgenDAV\EventInstance[]
     */
    public function expand(\DateTime $start, \DateTime $end)
    {
        $expanded_vcalendar = clone $this->vcalendar;
        $expanded_vcalendar->expand($start, $end);

        $result = [];

        foreach ($expanded_vcalendar->select('VEVENT') as $vevent) {
            $instance = $this->getExpandedInstance($vevent);

            $result[] = $instance;
        }

        return $result;
    }

    /**
     * Checks if a RECURRENCE-ID string (that could be the result of
     * expanding a recurrent event) was an exception to the rule or not
     *
     * @param string $recurrence_id RECURRENCE-ID value
     * @return boolean
     */
    public function isException($recurrence_id)
    {
        return isset($this->exceptions[$recurrence_id]);
    }

    /**
     * Returns an iCalendar string representation of this event
     *
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
     * @return \AgenDAV\EventInstance
     * @throws \LogicException If current event has no UID assigned
     */
    public function createEventInstance()
    {
        if (empty($this->uid)) {
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
     * Adds an EventInstance for this event. In case the event is not recurrent,
     * or it is but this is not an recurrence exception, it will get stored as the
     * "base" event instance
     *
     * @param \AgenDAV\EventInstance $instance
     * @throws \InvalidArgumentException If event instance UID does not match
     *                                   current event UID
     */
    public function storeInstance(EventInstance $instance)
    {
        // Check if UID matches
        if ($instance->getUid() !== $this->getUid()) {
            throw new \InvalidArgumentException('Event instance UID and self do not match');
        }

        // VObject sets a RECURRENCE-ID when expanding, so let's see if
        // this is a result of expanding or an actual recurrence exception
        // TODO
        if (!$instance->isException()) {
            $instance->setRecurrenceId(null);
        }

        // Add this event instance (case of empty VCALENDAR) or merge
        // with the existing one to avoid existing properties to be lost
        $base = $this->vcalendar->getBaseComponent('VEVENT');
        if ($base === null) {
            $instance->touch();
            $vevent = $instance->getInternalVEvent();
        } else {
            $resulting_instance = new VObjectEventInstance($base);
            $resulting_instance->copyPropertiesFrom($instance);
            $resulting_instance->touch();
            $vevent = $resulting_instance->getInternalVEvent();
        }

        if (!$instance->isException()) {
            VObjectHelper::setBaseVEvent($this->vcalendar, $vevent);
        } else {
            VObjectHelper::setExceptionVEvent($this->vcalendar, $vevent);
        }
    }

    /**
     * Gets the base EventInstance for this event if $recurrence_id is null,
     * or the EventInstance for the recurrence exception identified by
     * $recurrence_id.
     *
     * If the passed RECURRENCE-ID does not match any existing exceptions,
     * a new EventInstance will be created with RECURRENCE-ID set
     *
     * @param string|null $recurrence_id
     * @return \AgenDAV\EventInstance|null
     * @throws \LogicException if this event is not recurrent and a $recurrence_id
     * is specified
     */

    public function getEventInstance($recurrence_id = null)
    {
        if ($recurrence_id === null) {
            $vevent = $this->vcalendar->getBaseComponent('VEVENT');
        }

        if ($recurrence_id !== null) {
            $vevent = $this->getRecurrenceExceptionVEvent($recurrence_id);
        }

        if ($vevent === null) {
            return null;
        }

        $instance = new VObjectEventInstance($vevent);
        if ($recurrence_id !== null) {
            $instance->markAsException();
        }

        return $instance;
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

    /**
     * Gets a list of RECURRENCE-IDs defined for this event
     */
    protected function findRecurrenceExceptions(VCalendar $vcalendar)
    {
        $result = [];
        foreach ($vcalendar->select('VEVENT') as $vevent) {
            $recurrence_id = $vevent->{'RECURRENCE-ID'};
            if ($recurrence_id !== null) {
                $recurrence_id = (string)$recurrence_id;
                $result[$recurrence_id] = true;
            }
        }

        return $result;
    }

    /**
     * Finds UID for the base event instance
     *
     * @return string
     */
    protected function findUid()
    {
        $base_component = $this->vcalendar->getBaseComponent('VEVENT');

        if ($base_component === null) {
            return null;
        }

        return (string) $base_component->UID;
    }

    /**
     * Builds a VObjectEventInstance using the passed VEVENT object,
     * copying the recurrence rule and marking it as an exception in case
     * it is
     *
     * @param \Sabre\VObject\Component\VEvent $vevent
     * @return \AgenDAV\Event\VObjectEventInstance
     */
    protected function getExpandedInstance(VEvent $vevent)
    {
        $instance = new VObjectEventInstance($vevent);

        if ($this->repeat_rule !== null) {
            $instance->setRepeatRule($this->repeat_rule);

            $recurrence_id = $instance->getRecurrenceId();

            if ($this->isException($recurrence_id)) {
                $instance->markAsException();
            }
        }

        return $instance;
    }

    /**
     * Gets the VEVENT associated to the passed RECURRENCE-ID.
     *
     * If the event is not recurrent, a \LogicException will be thrown
     *
     * @param string $recurrence_id
     * @return \Sabre\VObject\Component\VEvent
     * @throws \LogicException if this event is not recurrent
     */
    protected function getRecurrenceExceptionVEvent($recurrence_id)
    {
        if (!$this->isRecurrent()) {
            throw new \LogicException('This event is not recurrent');
        }

        if ($this->isException($recurrence_id)) {
            return VObjectHelper::findExceptionVEvent($this->vcalendar, $recurrence_id);
        }

        // Create new VEVENT
        $vevent = clone $this->vcalendar->getBaseComponent('VEVENT');
        $vevent->{'RECURRENCE-ID'} = $recurrence_id;

        return $vevent;
    }
}

