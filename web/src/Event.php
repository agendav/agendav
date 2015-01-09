<?php

namespace AgenDAV;

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

/**
 * This interface models an Event
 *
 */
interface Event
{
    /**
     * Checks if current event is recurrent
     *
     * @return bool
     */
    public function isRecurrent();

    /**
     * Returns the UID for all event instances under this event
     */
    public function getUid();

    /**
     * Returns the RRULE for all event instances under this event
     */
    public function getRepeatRule();

    /**
     * Gets all event instances for a range of dates. If the event is not
     * recurrent, a single instance will be returned
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @return AgenDAV\EventInstance[]
     */
    public function expand(\DateTime $start, \DateTime $end);

    /**
     * Checks if a RECURRENCE-ID string (that could be the result of
     * expanding a recurrent event) was an exception to the rule or not
     *
     * @param string $recurrence_id RECURRENCE-ID value
     * @return boolean
     */
    public function isException($recurrence_id);

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
     * Sets base EventInstance for this event
     *
     * @param \AgenDAV\EventInstance $instance
     */
    public function setBaseEventInstance(EventInstance $instance);
}

