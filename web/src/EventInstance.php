<?php

namespace AgenDAV;

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

/**
 * This interface models an event instance (also known as 'expanded event')
 */
interface EventInstance
{
    /**
     * Return the UID for this VEVENT
     *
     * @return string
     */
    public function getUid();

    /**
     * Get the SUMMARY property of this event
     *
     * @return string
     */
    public function getSummary();

    /**
     * Get the LOCATION property of this event
     *
     * @return string
     */
    public function getLocation();

    /**
     * Get the DESCRIPTION property of this event
     *
     * @return string
     */
    public function getDescription();

    /**
     * Get the CLASS property of this event
     *
     * @return string
     */
    public function getClass();

    /**
     * Get the TRANSP property of this event
     *
     * @return string
     */
    public function getTransp();

    /**
     * Get the start of this event
     *
     * @return \DateTime
     */
    public function getStart();

    /**
     * Get the effective end of this event
     *
     * @return \DateTime
     */
    public function getEnd();

    /**
     * Check if this event is an all day event or not
     *
     * @return bool
     */
    public function isAllDay();

    /**
     * Check if this event repeats
     *
     * @return bool
     */
    public function isRecurrent();

    /**
     * Get the repeat rule of this event (RRULE)
     *
     * @return string
     */
    public function getRepeatRule();

    /**
     * Gets the RECURRENCE-ID property of this instance
     *
     * @return string
     */
    public function getRecurrenceId();

    /**
     * Set the SUMMARY property for this event
     *
     * @param string $summary
     */
    public function setSummary($summary);

    /**
     * Set the LOCATION property for this event
     *
     * @param string $location
     */
    public function setLocation($location);

    /**
     * Set the DESCRIPTION property for this event
     *
     * @param string $description
     */
    public function setDescription($description);

    /**
     * Set the CLASS property for this event
     *
     * @param string $class
     */
    public function setClass($class);

    /**
     * Set the TRANSP property for this event
     *
     * @param string $transp
     */
    public function setTransp($transp);

    /**
     * Set the start moment for this instance
     *
     * @param \DateTime $start
     * @param bool $all_day
     */
    public function setStart(\DateTime $start, $all_day = false);

    /**
     * Set the end moment for this instance
     *
     * @param \DateTime $end
     * @param bool $all_day
     */
    public function setEnd(\DateTime $end, $all_day = false);

    /**
     * Remove the RECURRENCE-ID property on this instance
     *
     * @return void
     */
    public function removeRecurrenceId();

    /**
     * Add (or updates) CREATED, LAST-MODIFIED, DTSTAMP and SEQUENCE
     *
     * @return void
     */
    public function touch();

    /**
     * Copy basic properties from another EventInstance to this instance
     *
     * @param \AgenDAV\EventInstance $source
     */
    public function copyPropertiesFrom(EventInstance $source);
}

