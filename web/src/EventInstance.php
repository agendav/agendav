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
 * This interface models an event instance (also known as 'expanded event')
 *
 */
interface EventInstance
{
    // TODO RRULE

    public function isRecurrent();

    public function getSummary();

    public function getLocation();

    public function getDescription();

    public function getClass();

    public function getTransp();

    public function getStart();

    public function getEnd();

    public function isAllDay();

    public function setSummary($summary);

    public function setLocation($location);

    public function setDescription($description);

    public function setClass($class);

    public function setTransp($transp);

    public function getRecurrenceId();

    public function setStart(\DateTime $start, $all_day = false);

    public function setEnd(\DateTime $end, $all_day = false);

    /**
     * Adds (or updates) CREATED, LAST-MODIFIED, DTSTAMP and SEQUENCE
     *
     * @return void
     */
    public function updateChangeProperties();

    /**
     * Copies basic properties from another EventInstance to this instance
     *
     * @param \AgenDAV\EventInstance $source
     */
    public function copyPropertiesFrom(EventInstance $source);
}

