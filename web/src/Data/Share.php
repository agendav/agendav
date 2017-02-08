<?php

namespace AgenDAV\Data;

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

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Principal;


/**
 * Holds information about a calendar that has been shared
 */

/**
 * @Entity @HasLifecycleCallbacks
 * @Table(name="shares")
 */
class Share
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $sid;

    /** @Column(type="string") */
    private $owner;

    /** @Column(type="string") */
    private $calendar;

    /** @Column(name="`with`", type="string") */
    private $with;

    private $with_principal;

    /** @Column(type="array") */
    private $options = array();

    /** @Column(type="boolean") */
    private $rw;


    /*
     * Getter for sid
     *
     * @return int
     */
    public function getSid()
    {
        return $this->sid;
    }

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

    /*
     * Returns the grantee principal URL
     *
     * @return string
     */
    public function getWith()
    {
        return $this->with;
    }

    /*
     * Sets the principal URL this is calendar is shared with
     *
     * @param string $with
     */
    public function setWith($with)
    {
        $this->with = $with;
        return $this;
    }

    /*
     * Returns Share associated principal, if set
     *
     * @return AgenDAV\Data\Principal
     */
    public function getPrincipal()
    {
        return $this->with_principal;
    }
    
    /*
     * Sets this share associated Principal
     *
     * @param AgenDAV\Data\Principal $principal
     */
    public function setPrincipal(Principal $principal)
    {
        $this->with_principal = $principal;
        return $this;
    }
    

    /*
     * Returns true if a share allows modifications
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->rw === true;
    }

    /*
     * Setter for rw
     *
     * @param bool $rw  Whether this share allows modifications or not
     */
    public function setWritePermission($rw)
    {
        $this->rw = $rw;
    }

    /**
     * Returns all properties/options set on the shared resource
     *
     * @return Array
     */
    public function getProperties()
    {
        if (!is_array($this->options)) {
            return array();
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
