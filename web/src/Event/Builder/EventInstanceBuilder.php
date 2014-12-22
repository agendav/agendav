<?php

namespace AgenDAV\Event\Builder;

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
 * Interface to generate a new EventInstance
 */
interface EventInstanceBuilder
{
    /**
     * Creates an empty EventInstance object
     *
     * @param \AgenDAV\Event $event Event this instance will be attached to
     * @return \AgenDAV\EventInstance
     * @throws \LogicException If $event has no UID assigned
     */
    public function createFor(\AgenDAV\Event $event);

    /**
     * Creates an EventInstance object after receiving an array of properties
     * with the following keys:
     *
     * summary
     * location
     * start_date
     * start_time
     * end_date
     * end_time
     * allday
     * description
     * class
     * transp
     * TODO: recurrence rules, reminders, recurrence-id
     *
     * @param \AgenDAV\Event $event Parent event
     * @param array $attributes
     * @return \AgenDAV\EventInstance
     */
    public function createFromInput(\AgenDAV\Event $event, array $attributes);
}
