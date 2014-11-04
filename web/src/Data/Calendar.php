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
     * @param string $displayname   Display name for this calendar
     * @param array $attributes More attributes for this calendar
     */
    public function __construct($url, $displayname = '', $attributes = [])
    {
        $this->url = $url;
        $this->data = self::$defaults;
        $this->data['displayname'] = $displayname;
        $this->data = array_merge($this->data, $attributes);
    }

    public function __get($attr)
    {
        // Backwards compatibility
        if ($attr == 'calendar') {
            $attr = 'url';
        }

        if ($attr == 'url') {
            return $this->url;
        }

        return array_key_exists($attr, $this->data) ?
            $this->data[$attr] :
            null;
    }

    public function __set($attr, $value)
    {
        // Backwards compatibility
        if ($attr == 'calendar') {
            $attr = 'url';
        }

        if ($attr == 'url') {
            return $this->url;
        }

        $this->data[$attr] = $value;
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
        $data['url'] = $this->url;
        $data['calendar'] = $this->url;

        return $data;
    }
}
