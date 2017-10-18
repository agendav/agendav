<?php

namespace AgenDAV\Event;

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

use AgenDAV\Event;
use AgenDAV\EventInstance;
use AgenDAV\Event\VObjectEventInstance;
use AgenDAV\Event\VObjectHelper;
use AgenDAV\Exception\NotFound;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;

/**
 * VObject implementation of Events
 *
 */
class VObjectEvent implements Event
{
    /** @var \Sabre\VObject\Component\VCalendar */
    protected $vcalendar;

    /** @var bool */
    protected $is_recurrent;

    /** @var string */
    protected $repeat_rule;

    /** @var \DateTimeImmutable[] */
    protected $exceptions;

    /** @var \DateTimeImmutable[] */
    protected $removed_instances;

    /** @var string */
    protected $uid;

    /**
     * Builds a new VObjectEvent
     *
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     */
    public function __construct(VCalendar $vcalendar)
    {
        $this->vcalendar = $vcalendar;
        $this->uid = $this->findUid();

        $this->updateRecurrentStatus();

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
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @return \AgenDAV\EventInstance[]
     */
    public function expand(\DateTimeInterface $start, \DateTimeInterface $end)
    {
        $expanded_vcalendar = $this->vcalendar->expand($start, $end);

        $base_instance = $this->getEventInstance();

        $result = [];

        foreach ($expanded_vcalendar->select('VEVENT') as $vevent) {
            $instance = $this->getExpandedInstance($vevent);

            $result[] = $instance;
        }

        return $result;
    }

    /**
     * Checks if this event has any recurrence exceptions or removed instances
     *
     * @return boolean
     */
    public function hasExceptions()
    {
        if (!$this->isRecurrent()) {
            return false;
        }

        return count($this->exceptions) > 0 || count($this->removed_instances) > 0;
    }

    /**
     * Checks if a RECURRENCE-ID string (that could be the result of
     * expanding a recurrent event) was an exception to the rule or not
     *
     * @param \AgenDAV\Event\RecurrenceId $recurrence_id
     * @return boolean
     */
    public function isException(RecurrenceId $recurrence_id = null)
    {
        if ($recurrence_id === null) {
            return false;
        }

        $recurrence_datetime = $recurrence_id->getDateTime(); // UTC
        foreach ($this->exceptions as $exception_datetime) {
            // Comparing two \DateTime objects with different timezones is allowed
            if ($recurrence_datetime == $exception_datetime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a RECURRENCE-ID is a removed instance from the recurrence
     *
     * @param \AgenDAV\Event\RecurrenceId $recurrence_id
     * @return boolean
     */
    public function isRemovedInstance(RecurrenceId $recurrence_id = null)
    {
        if ($recurrence_id === null) {
            return false;
        }

        $recurrence_datetime = $recurrence_id->getDateTime();
        foreach ($this->removed_instances as $removed_datetime) {
            // Comparing two \DateTime objects with different timezones is allowed
            if ($recurrence_datetime == $removed_datetime) {
                return true;
            }
        }

        return false;
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
     * @throws \LogicException If a recurrence exception is passed for a date
     *                         that is removed (EXDATE)
     */
    public function storeInstance(EventInstance $instance)
    {
        // Check if UID matches
        if ($instance->getUid() !== $this->getUid()) {
            throw new \InvalidArgumentException('Event instance UID and self do not match');
        }

        // VObject sets a RECURRENCE-ID when expanding, so let's see if
        // this is a result of expanding or an actual recurrence exception
        if (!$instance->isException()) {
            $instance->setRecurrenceId(null);

            // Limitation: can't modify base VEVENT if exceptions are set
            // TODO improve this?
            if ($this->hasExceptions()) {
                VObjectHelper::removeAllExceptions($this->vcalendar);
                $this->exceptions = $this->findRecurrenceExceptions($this->vcalendar);
            }
        }

        // New exception for a previously removed instance (EXDATE)?
        if ($instance->isException()) {
            $recurrence_id = $instance->getRecurrenceId();

            if ($this->isRemovedInstance($recurrence_id)) {
                throw new \LogicException(
                    'Cannot add a new exception for a previously removed instance'
                );
            }
        }

        // Add this event instance (case of empty VCALENDAR) or merge
        // with the existing one to avoid existing properties to be lost
        $base = $this->vcalendar->getBaseComponent('VEVENT');

        // Existing exception
        if ($this->isException($instance->getRecurrenceId())) {
            $base = VObjectHelper::findExceptionVEvent(
                $this->vcalendar,
                $instance->getRecurrenceId()
            );
        }

        if ($base === null) {
            $instance->touch();
            $vevent = $instance->getInternalVEvent();
        } else {
            $base = clone $base;
            $resulting_instance = new VObjectEventInstance($base);
            $resulting_instance->copyPropertiesFrom($instance);
            $resulting_instance->touch();
            $resulting_instance->setRecurrenceId($instance->getRecurrenceId());
            $vevent = $resulting_instance->getInternalVEvent();
        }

        if (!$instance->isException()) {
            VObjectHelper::setBaseVEvent($this->vcalendar, $vevent);
        } else {
            VObjectHelper::setExceptionVEvent($this->vcalendar, $vevent);
        }

        $this->updateRecurrentStatus();
    }

    /**
     * Removes an event instance by its RECURRENCE-ID from this event
     *
     * @param \AgenDAV\Event\RecurrenceId $recurrence_id
     * @throws \LogicException if this event is not recurrent
     * @throws \AgenDAV\Exception\NotFound if the instance was already removed
     */
    public function removeInstance(RecurrenceId $recurrence_id)
    {
        if (!$this->isRecurrent()) {
            throw new \LogicException('Tried to remove an instance from a non recurrent event');
        }

        if ($this->isRemovedInstance($recurrence_id)) {
            throw new NotFound(
                'Tried to remove an already removed instance: ' . $recurrence_id->getString()
            );
        }

        // If there was an exception defined for the passed RECURRENCE-ID,
        // remove it
        if ($this->isException($recurrence_id)) {
            $vevent = VObjectHelper::findExceptionVEvent($this->vcalendar, $recurrence_id);
            $this->vcalendar->remove($vevent);

            // Now update the list of recognized recurrence exceptions
            $this->exceptions = $this->findRecurrenceExceptions($this->vcalendar);
        }

        // Add a new value to the EXDATE property of the base VEVENT
        $base_instance = $this->getEventInstance();
        $vevent = $base_instance->getInternalVEvent();
        $recurrence_datetime = $recurrence_id->getDateTime();
        $new_exdates = VObjectHelper::addExdateToVEvent($vevent, $recurrence_datetime);

        // getInternalVEvent creates a copy of the internal VEvent, so we have to
        // store it back
        VObjectHelper::setBaseVEvent($this->vcalendar, $vevent);

        $this->removed_instances = $new_exdates;
    }

    /**
     * Gets the base EventInstance for this event if $recurrence_id is null,
     * or the EventInstance for the recurrence exception identified by
     * $recurrence_id.
     *
     * If the passed RECURRENCE-ID does not match any existing exceptions,
     * a new EventInstance will be created with RECURRENCE-ID set
     *
     * @param \AgenDAV\Event\RecurrenceId|null $recurrence_id
     * @return \AgenDAV\EventInstance|null
     * @throws \LogicException if this event is not recurrent and a $recurrence_id
     * @throws \AgenDAV\Exception\NotFound if the instance was removed
     * is specified
     */

    public function getEventInstance(RecurrenceId $recurrence_id = null)
    {
        $vevent = null;

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
            $instance->setHasExceptions($this->hasExceptions());

            // Update instance start and end based on RECURRENCE-ID
            if (!$this->isException($recurrence_id)) {
                $instance->updateForRecurrenceId($recurrence_id);
            }
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
     *
     * @return \DateTimeImmutable[]
     */
    protected function findRecurrenceExceptions(VCalendar $vcalendar)
    {
        $result = [];
        foreach ($vcalendar->select('VEVENT') as $vevent) {
            $recurrence_id = $vevent->{'RECURRENCE-ID'};
            if ($recurrence_id === null) {
                continue;
            }

            $result[] = $recurrence_id->getDateTime();
        }

        return $result;
    }

    /**
     * Gets a list of Removed instances for this event
     *
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     * @return \DateTimeImmutable[]
     */
    protected function findRemovedInstances(VCalendar $vcalendar)
    {
        $base = $vcalendar->getBaseComponent();
        $exdate = $base->EXDATE;

        if ($exdate === null) {
            return [];
        }

        return $exdate->getDateTimes();
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

        if ($this->isRecurrent()) {
            $instance->setRepeatRule($this->repeat_rule);

            $recurrence_id = $instance->getRecurrenceId();

            if ($recurrence_id !== null && $this->isException($recurrence_id)) {
                $instance->markAsException();
            }

            // Make the instance know if the parent event has any exceptions
            $instance->setHasExceptions($this->hasExceptions());
        }

        return $instance;
    }

    /**
     * Gets the VEVENT associated to the passed RECURRENCE-ID.
     *
     * If the event is not recurrent, a \LogicException will be thrown
     *
     * @param \AgenDAV\Event\RecurrenceId $recurrence_id
     * @return \Sabre\VObject\Component\VEvent
     * @throws \LogicException if this event is not recurrent
     * @throws \AgenDAV\Exception\NotFound if the instance was removed
     */
    protected function getRecurrenceExceptionVEvent(RecurrenceId $recurrence_id)
    {
        if (!$this->isRecurrent()) {
            throw new \LogicException('This event is not recurrent');
        }

        if ($this->isException($recurrence_id)) {
            return VObjectHelper::findExceptionVEvent($this->vcalendar, $recurrence_id);
        }

        if ($this->isRemovedInstance($recurrence_id)) {
            throw new NotFound('Event instance is marked as removed');
        }

        // Create new VEVENT
        $vevent = clone $this->vcalendar->getBaseComponent('VEVENT');
        unset($vevent->RRULE);
        $vevent->{'RECURRENCE-ID'} = $recurrence_id->getDateTime();

        return $vevent;
    }


    /**
     * Checks if current event is recurrent. In case it is, sets required properties
     *
     */
    private function updateRecurrentStatus()
    {
        $this->is_recurrent = false;
        $this->exceptions = [];

        $this->repeat_rule = $this->extractRRule();

        if ($this->repeat_rule !== null) {
            $this->is_recurrent = true;
            $this->exceptions = $this->findRecurrenceExceptions($this->vcalendar);
            $this->removed_instances = $this->findRemovedInstances($this->vcalendar);
        }
    }
}

