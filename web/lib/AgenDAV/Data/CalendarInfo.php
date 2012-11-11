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
class CalendarInfo
{
    /**
     * Calendar attributes
     *
     * @var array
     * @access private
     */
    private $data;

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
        'share_with' => array(),
        'write_access' => true,
    );


    public function __construct($url, $displayname = '', $getctag = null )
    {
        $this->data = self::$defaults;
        $this->data['url'] = $url;
        $this->data['displayname'] = $displayname;
        $this->data['getctag'] = $getctag;
    }

    public function __get($attr)
    {
        // Backwards compatibility
        if ($attr == 'calendar') {
            $attr = 'url';
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
        $tmp = $this->data;
        // Backwards compatibility
        $tmp['calendar'] = $tmp['url'];

        return $tmp;
    }
}
