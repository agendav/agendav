<?php 

namespace AgenDAV\Data;

/*
 * Copyright 2012 Jorge López Pérez <jorge@adobo.org>
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
     * Calendar attributes
     *
     * @var array
     */
    protected $data;

    /**
     * Property names including namespaces
     */
    const DISPLAYNAME = '{DAV:}displayname';
    const CTAG = '{http://calendarserver.org/ns/}getctag';
    const COLOR = '{http://apple.com/ns/ical/}calendar-color';
    const ORDER = '{http://apple.com/ns/ical/}calendar-order';

    /**
     * Default attributes 
     */
    public static $defaults = array(
        'displayname' => '',
        'getctag' => null,
        'order' => null,
        'color' => null,
        'shared' => false,
        'is_default' => false,
        'grantee' => array(),
        'rw' => true,
    );


    /**
     * Creates a new calendar
     *
     * @param string $url   Calendar URL
     * @param array $properties More attributes for this calendar
     */
    public function __construct($url, $properties = [])
    {
        $this->url = $url;
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }
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
        return array_key_exists($property, $this->data) ?
            $this->data[$property] :
            null;
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
        if ($property == 'url') {
            throw new \RuntimeException('Calendar URL cannot be changed');
        }

        $this->data[$property] = $value;
    }

    /**
     * Returns all calendar attributes. Useful for JSON encoding until PHP 5.4 JsonSerializable
     * is widely available
     * 
     * @access public
     * @return array
     */
    public function getAll()
    {
        $data = $this->data;
        // Backwards compatibility
        $data['url'] = $this->getUrl();

        return $data;
    }
}
