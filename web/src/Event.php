<?php

namespace AgenDAV;

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

use AgenDAV\Event\RecurrenceId;

/**
 * This interface models an Event
 *
 */
interface Event
{
    /**
     * Returns the UID for all event instances under this event
     *
     * @return string
     */
    public function getUid();

    /**
     * Sets UID for this event.
     *
     * @param string $uid
     * @throws \LogicException if this event already has an UID assigned
     */
    public function setUid($uid);

    /**
     * Checks if current event is recurrent
     *
     * @return bool
     */
    public function isRecurrent();


    /**
     * Returns the RRULE for all event instances under this event
     *
     * @return string
     */
    public function getRepeatRule();

    /**
     * Gets all event instances for a range of dates. If the event is not
     * recurrent, a single instance will be returned
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @return \AgenDAV\EventInstance[]
     */
    public function expand(\DateTimeInterface $start, \DateTimeInterface $end);

    /**
     * Checks if this event has any recurrence exceptions or removed instances
     *
     * @return boolean
     */
    public function hasExceptions();

    /**
     * Checks if a RECURRENCE-ID is an exception to the repeat rule or not
     *
     * @param \AgenDAV\Event\RecurrenceId $recurrence_id
     *
     * @return boolean
     */
    public function isException(RecurrenceId $recurrence_id = null);

    /**
     * Checks if a RECURRENCE-ID is a removed instance from the recurrence
     *
     * @param \AgenDAV\Event\RecurrenceId $recurrence_id
     *
     * @return boolean
     */
    public function isRemovedInstance(RecurrenceId $recurrence_id = null);

    /**
     * Returns an iCalendar string representation of this event
     *
     * @return string
     */
    public function render();

    /**
     * Creates a new EventInstance for this event. If the event already
     * had a base event instance assigned, a copy of it will be returned.
     *
     * If not, a clean event instance will be returned.
     *
     * @return \AgenDAV\EventInstance
     * @throws \LogicException If current event has no UID assigned
     */
    public function createEventInstance();

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

    public function getEventInstance(RecurrenceId $recurrence_id = null);

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
    public function storeInstance(EventInstance $instance);

    /**
     * Removes an event instance by its RECURRENCE-ID from this event
     *
     * @param \AgenDAV\Event\RecurrenceId $recurrence_id
     * @throws \LogicException if this event is not recurrent
     * @throws \AgenDAV\Exception\NotFound if the instance was already removed
     */
    public function removeInstance(RecurrenceId $recurrence_id);
}

