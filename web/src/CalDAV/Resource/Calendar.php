<?php

namespace AgenDAV\CalDAV\Resource;

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

use \AgenDAV\Data\Share;
use \AgenDAV\Data\Principal;

/**
 * Stores information about a calendar collection
 */
class Calendar
{
    /**
     * URL of this calendar
     *
     * @var string
     */
    protected $url;

    /**
     * Calendar properties
     *
     * @var array
     */
    protected $properties;

    /**
     * Writable calendar
     *
     * @var boolean
     */
    protected $writable;

    /**
     * Subscribed calendar
     *
     * @var boolean
     */
    protected $subscribed;

    /**
     * Owner of this calendar.
     *
     * Required on shared calendars
     *
     * @var \AgenDAV\Data\Principal
     */
    protected $owner;

    /**
     * Shares for this calendar, when working on shared calendars
     *
     * @var \AgenDAV\Data\Share[]
     */
    protected $shares;

    /**
     * Property names including namespaces
     */
    const DISPLAYNAME = '{DAV:}displayname';
    const CTAG = '{http://calendarserver.org/ns/}getctag';
    const COLOR = '{http://apple.com/ns/ical/}calendar-color';
    const ORDER = '{http://apple.com/ns/ical/}calendar-order';

    /**
     * Properties that AgenDAV can write
     */
    public static $writable_properties = [
        '{DAV:}displayname',
        '{http://apple.com/ns/ical/}calendar-color',
    ];

    /**
     * Creates a new calendar
     *
     * @param string $url   Calendar URL
     * @param array $properties More properties for this calendar
     */
    public function __construct($url, $properties = [])
    {
        $this->url = $url;
        $this->properties = [];
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }

        $this->writable = true;
        $this->subscribed = false;
        $this->shares = [];
    }

    /*
     * Getter for URL
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns a property value from this calendar
     *
     * @param string $property Property to return
     * @return mixed Stored value, or null if the property is missing
     */
    public function getProperty($property)
    {
        if (array_key_exists($property, $this->properties) && !empty($this->properties[$property])) {
            return $this->properties[$property];
        }

        return null;
    }


    /**
     * Sets a property value for this calendar
     *
     * @param string $property  Property name
     * @param mixed $value  Value
     */
    public function setProperty($property, $value)
    {
        // Backwards compatibility
        if ($property === 'url') {
            throw new \RuntimeException('Calendar URL cannot be changed');
        }

        // RGBA colors
        if ($property === self::COLOR) {
            $this->properties[self::COLOR] = $this->ensureRgbaColor($value);
            return;
        }

        $this->properties[$property] = $value;
    }

    /**
     * Returns all properties set for this calendar, excluding the URL
     *
     * @return array Properties (associative array), in Clark notation
     */
    public function getAllProperties()
    {
        return $this->properties;
    }

    /**
     * Returns all writable properties from this calendar
     *
     * @return array Properties (associative array), in Clark notation
     */
    public function getWritableProperties()
    {
        return array_intersect_key(
            $this->properties,
            array_flip(self::$writable_properties)
        );
    }

    /*
     * Getter for writable
     *
     * @return boolean
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /*
     * Setter for writable
     *
     * @param boolean $writable
     */
    public function setWritable($writable)
    {
        $this->writable = $writable;
    }


    /*
     * Getter for subscribed
     *
     * @return boolean
     */
    public function isSubscribed()
    {
        return $this->subscribed;
    }

    /*
     * Setter for subscribed
     *
     * @param boolean $subscribed
     */
    public function setSubscribed($subscribed)
    {
        $this->subscribed = $subscribed;
    }

    /*
     * Getter for owner
     */
    public function getOwner()
    {
        return $this->owner;
    }
    /*
     * Setter for owner
     */
    public function setOwner(Principal $owner)
    {
        $this->owner = $owner;
    }

    /*
     * Getter for shares
     *
     * @return \AgenDAV\Data\Share[]
     */
    public function getShares()
    {
        return $this->shares;
    }

    /*
     * Setter for shares
     *
     * @param \AgenDAV\Data\Share[]
     */
    public function setShares(array $shares)
    {
        $this->shares = $shares;
    }

    /**
     * Adds a new share to this calendar
     *
     * @param \AgenDAV\Data\Share $share
     */
    public function addShare(Share $share)
    {
        $this->shares[] = $share;
    }

    /**
     * Removes a Share from this calendar
     *
     * @param \AgenDAV\Data\Share $share
     * @return boolean true if the share was found and removed, false otherwise
     */
    public function removeShare(Share $share_to_remove)
    {
        $searched_sid = $share_to_remove->getSid();
        foreach ($this->shares as $position => $share) {
            if ($share->getSid() === $searched_sid) {
                unset($this->shares[$position]);
                return true;
            }
        }

        return false;
    }

    /**
     * Modifies the provided color to make sure it has an alpha channel
     *
     * @param string $color
     * @result string
     */
    protected function ensureRgbaColor($color)
    {
        // Missing alpha channel
        if (strlen($color) === 7) {
            return $color . 'ff';
        }

        if (strlen($color) === 4) {
            preg_match('/#(.)(.)(.)/', $color, $matches);
            return '#' .
                $matches[1] . $matches[1] .
                $matches[2] . $matches[2] .
                $matches[3] . $matches[3] .
                'ff';
        }

        return $color;
    }

}
