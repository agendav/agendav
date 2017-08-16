<?php

namespace AgenDAV\Data;

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

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Principal;


/**
 * Holds information about a calendar that has been subscribed
 */

/**
 * @Entity @HasLifecycleCallbacks
 * @Table(name="subscriptions")
 */
class Subscription
{

    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $sid;

    /** @Column(type="string") */
    private $calendar;

    /** @Column(type="string") */
    private $owner;

    /** @Column(type="array") */
    private $options = [];


    /*
     * Returns the owner principal URL
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /*
     * Setter for owner
     *
     * @param string $owner Owner principal URL
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /*
     * Returns the calendar URL
     *
     * @return string
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /*
     * Setter for calendar
     *
     * @param string $calendar
     */
    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;
        return $this;
    }

    /**
     * Returns all properties/options set on the shared resource
     *
     * @return Array
     */
    public function getProperties()
    {
        if ($this->options === null) {
            return [];
        }

        return $this->options;
    }

    /**
     * Returns a property/option set on this resource, or null if it
     * is not set
     *
     * @param string $name
     * @return string|null
     */
    public function getProperty($name)
    {
        if ($this->options === null) {
            return null;
        }

        return array_key_exists($name, $this->options) ?  $this->options[$name] : null;
    }

    /**
     * Sets a property/option on this resource
     *
     * @param string $name
     * @param string $value
     */
    public function setProperty($name, $value)
    {
        if ($this->options === null) {
            $this->options = [];
        }

        $this->options[$name] = $value;
    }

    /**
     * Applies custom properties to passed calendar object
     *
     * @param AgenDAV\CalDAV\Resource\Calendar $calendar
     */
    public function applyCustomPropertiesTo(Calendar $calendar)
    {
        foreach ($this->getProperties() as $property => $value) {
            $calendar->setProperty($property, $value);
        }
    }

    /** @PostLoad */
    public function replaceOldProperties() {
        $replacements = [
            'displayname' => Calendar::DISPLAYNAME,
            'color' => Calendar::COLOR,
        ];

        foreach ($replacements as $old_name => $new_name) {
            $old_value = $this->getProperty($old_name);
            $new_value = $this->getProperty($new_name);

            if ($old_value !== null && $new_value === null) {
                $this->setProperty($new_name, $old_value);
            }

            // Just remove it
            unset($this->options[$old_name]);
        }
    }
}
