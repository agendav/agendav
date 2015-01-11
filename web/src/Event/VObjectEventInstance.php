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
use AgenDAV\Data\Reminder;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Property\ICalendar\DateTime;

/**
 * VObject implementation of expanded events (event instances)
 */

class VObjectEventInstance implements EventInstance
{

    protected $vevent;

    protected $reminders;

    /**
     * Builds a new VObjectEventInstance
     *
     * @param Sabre\VObject\Component\VEvent $vevent
     */
    public function __construct(VEvent $vevent)
    {
        $this->vevent = $vevent;
        $this->is_recurrent = isset($vevent->RRULE);
        $this->reminders = $this->findReminders();
    }

    /**
     * Returns the UID for this VEVENT
     *
     * @return string
     */
    public function getUid()
    {
        return (string) $this->vevent->UID;
    }

    /**
     * Get the SUMMARY property of this event
     *
     * @return string
     */
    public function getSummary()
    {
        return (string) $this->vevent->SUMMARY;
    }

    /**
     * Get the LOCATION property of this event
     *
     * @return string
     */
    public function getLocation()
    {
        return (string) $this->vevent->LOCATION;
    }

    /**
     * Get the DESCRIPTION property of this event
     *
     * @return string
     */
    public function getDescription()
    {
        return (string) $this->vevent->DESCRIPTION;
    }

    /**
     * Get the CLASS property of this event
     *
     * @return string
     */
    public function getClass()
    {
        return (string) $this->vevent->CLASS;
    }

    /**
     * Get the TRANSP property of this event
     *
     * @return string
     */
    public function getTransp()
    {
        return (string) $this->vevent->TRANSP;
    }

    /**
     * Get the start of this event
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->vevent->DTSTART->getDateTime();
    }

    /**
     * Get the effective end of this event
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        if (isset($this->vevent->DTEND)) {
            return $this->vevent->DTEND->getDateTime();
        }
        // This is the starting point for every other case
        $end = $this->getStart();

        if (isset($this->vevent->DURATION)) {
            $end->add(DateTimeParser::parseDuration($this->vevent->DURATION));
            return $end;
        }

        if ($this->isAllDay()) {
            $end->modify('+1 day');
            return $end;
        }

        return $end;
    }

    /**
     * Check if this event is an all day event or not
     *
     * @return bool
     */
    public function isAllDay()
    {
        if (!$this->vevent->DTSTART->hasTime()) {
            return true;
        }

        return false;
    }

    /**
     * Check if this event repeats
     *
     * @return bool
     */
    public function isRecurrent()
    {
        return $this->is_recurrent;
    }

    /**
     * Get the repeat rule of this event (RRULE)
     *
     * @return string
     */
    public function getRepeatRule()
    {
        return (string) $this->vevent->RRULE;
    }

    /**
     * Gets the RECURRENCE-ID property of this instance
     *
     * @return string
     */
    public function getRecurrenceId()
    {
        return (string) $this->vevent->{'RECURRENCE-ID'};
    }

    /**
     * Returns all recognized reminders for this instance
     *
     * @return AgenDAV\Data\Reminder[]
     */
    public function getReminders()
    {
        return $this->reminders;
    }

    /**
     * Set the SUMMARY property for this event
     *
     * @param string $summary
     */
    public function setSummary($summary)
    {
        $this->setProperty('SUMMARY', $summary);
    }

    /**
     * Set the LOCATION property for this event
     *
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->setProperty('LOCATION', $location);
    }

    /**
     * Set the DESCRIPTION property for this event
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->setProperty('DESCRIPTION', $description);
    }

    /**
     * Set the CLASS property for this event
     *
     * @param string $class
     */
    public function setClass($class)
    {
        $this->setProperty('CLASS', $class);
    }

    /**
     * Set the TRANSP property for this event
     *
     * @param string $transp
     */
    public function setTransp($transp)
    {
        $this->setProperty('TRANSP', $transp);
    }

    /**
     * Set the start moment for this instance
     *
     * @param \DateTime $start
     * @param bool $all_day
     */
    public function setStart(\DateTime $start, $all_day = false)
    {
        $this->vevent->DTSTART = $start;
        if ($all_day === true) {
            $this->vevent->DTSTART['VALUE'] = 'DATE';
        }
    }

    /**
     * Set the end moment for this instance
     *
     * @param \DateTime $end
     * @param bool $all_day
     */
    public function setEnd(\DateTime $end, $all_day = false)
    {
        // We prefer DTEND to DURATION
        if (isset($this->vevent->DURATION)) {
            $this->vevent->remove('DURATION');
        }

        $this->vevent->DTEND = $end;
        if ($all_day === true) {
            $this->vevent->DTEND['VALUE'] = 'DATE';
        }
    }

    /**
     * Set the repeat rule for this event
     *
     * @param string $rrule
     */
    public function setRepeatRule($rrule)
    {
        $this->setProperty('RRULE', $rrule);
    }

    /**
     * Set the RECURRENCE-ID property for this event
     *
     * @param string $recurrence_id
     */
    public function setRecurrenceId($recurrence_id)
    {
        $this->setProperty('RECURRENCE-ID', $recurrence_id);
    }

    /**
     * Removes the RECURRENCE-ID property on this instance
     *
     * @return void
     */
    public function removeRecurrenceId()
    {
        unset($this->vevent->{'RECURRENCE-ID'});
    }

    /**
     * Adds (or updates) CREATED, LAST-MODIFIED, DTSTAMP and SEQUENCE
     *
     * @return void
     */
    public function touch()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!isset($this->vevent->CREATED) || !isset($this->vevent->DTSTAMP)) {
            $this->vevent->CREATED = $now;
            $this->vevent->DTSTAMP = $now;
        }

        $this->vevent->{'LAST-MODIFIED'} = $now;
        $sequence = 0;
        if (isset($this->vevent->SEQUENCE)) {
            $sequence = $this->vevent->SEQUENCE->getValue() + 1;
        }
        $this->vevent->SEQUENCE = $sequence;
    }

    /**
     * Copies basic properties from another EventInstance to this instance
     *
     * @param \AgenDAV\EventInstance $source
     */
    public function copyPropertiesFrom(EventInstance $source)
    {
        $this->setSummary($source->getSummary());
        $this->setLocation($source->getLocation());
        $this->setDescription($source->getDescription());
        $this->setClass($source->getClass());
        $this->setTransp($source->getTransp());
        $all_day = $source->isAllDay();
        $this->setStart($source->getStart(), $all_day);
        $this->setEnd($source->getEnd(), $all_day);
        $this->setRepeatRule($source->getRepeatRule());

        $this->touch();
    }

    /**
     * Return a copy the internal VEvent object
     *
     * @return Sabre\VObject\Component\VEvent
     */
    public function getInternalVEvent()
    {
        $vevent = clone $this->vevent;

        return $vevent;
    }


    /**
     * Set a property on the internal VEVENT. If a null
     * value is provided, the property gets removed from the resource
     *
     * @param string $property_name
     * @param mixed $value
     */
    protected function setProperty($property_name, $value)
    {
        if (empty($value)) {
            unset($this->vevent->{$property_name});
            return;
        }
        $this->vevent->{$property_name} = $value;
    }

    /**
     * Finds all VALARMs on this instance
     *
     * @return \AgenDAV\Data\Reminder[]
     */
    protected function findReminders()
    {
        $result = [];

        $valarms = $this->vevent->select('VALARM');
        $position = 1;

        foreach ($valarms as $valarm) {
            $reminder = Reminder::createFromVObject($valarm, $position);
            if ($reminder !== null) {
                $result[] = $reminder;
            }
            $position++;
        }

        return $result;
    }

}
