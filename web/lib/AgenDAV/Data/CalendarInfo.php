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
    public $calendar;

    public $url;

    public $displayname;

    public $getctag;

    public $order;

    public $color;

    public $shared;

    public $is_default;

    public $share_with;

    public $write_access;


    public function __construct($url, $displayname = null, $getctag = null ) {
        $this->url = $url;
        $this->displayname = $displayname;
        $this->getctag = $getctag;
        $this->is_default = false;
        $this->order = false;
        $this->shared = false;
    }
}
