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
    private $data = array();

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

    public function __construct(array $info)
    {
        if (!isset($info['calendar'])) {
            throw new \InvalidArgumentException('Calendar id was not provided');
        } elseif (!isset($info['url'])) {
            throw new \InvalidArgumentException('Calendar URL was not provided');
        }

        $this->set($info);
    }

    public function get()
    {
        return $this->data;
    }

    public function set(array $info)
    {
        $this->data = self::$defaults;
        $this->data = array_merge($this->data, $info);
    }
}
