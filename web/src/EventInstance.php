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

use AgenDAV\Event\RecurrenceId;

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
     * @return \DateTimeImmutable
     */
    public function getStart();

    /**
     * Get the effective end of this event
     *
     * @return \DateTimeImmutable
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
     * @return AgenDAV\Event\RecurrenceId
     */
    public function getRecurrenceId();

    /**
     * Returns all recognized reminders for this instance
     *
     * @return AgenDAV\Data\Reminder[]
     */
    public function getReminders();

    /**
     * Adds a new reminder
     *
     * @param AgenDAV\Data\Reminder
     */
    public function addReminder(\AgenDAV\Data\Reminder $reminder);

    /**
     * Removes all recognized reminders from this instance
     *
     * @return void
     */
    public function clearReminders();

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
     * @param \DateTimeImmutable $start
     * @param bool $all_day
     */
    public function setStart(\DateTimeImmutable $start, $all_day = false);

    /**
     * Set the end moment for this instance
     *
     * @param \DateTimeImmutable $end
     * @param bool $all_day
     */
    public function setEnd(\DateTimeImmutable $end, $all_day = false);

    /**
     * Set the repeat rule for this event
     *
     * @param string $rrule
     */
    public function setRepeatRule($rrule);

    /**
     * Set the RECURRENCE-ID property for this event
     *
     * @param AgenDAV\Event\RecurrenceId|null $recurrence_id
     */
    public function setRecurrenceId(RecurrenceId $recurrence_id = null);

    /**
     * Sets the exception status for this instance. This is useful on
     * recurrent events which have exceptions (with their own RECURRENCE-ID)
     *
     * @param bool $is_exception
     */
    public function markAsException($is_exception = true);

    /**
     * Gets the exception status for this instance
     *
     * @return bool
     */
    public function isException();

    /**
     * Sets the 'hasExceptions' flag on this event instance. This means
     * that this instance comes from an event that has one or more
     * recurrence exceptions
     *
     * @param bool $new_value defaults to true
     */
    public function setHasExceptions($new_value = true);

    /**
     * Checks if parent event has any recurrence exceptions or removed instances
     *
     * @return boolean
     */
    public function hasExceptions();

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

