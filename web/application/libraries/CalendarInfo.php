<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

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

class CalendarInfo {
    public $url, $displayname, $getctag;
    public $calendar, $order, $color, $shared, $rgba_color, $is_default;
    public $share_with = array();

    public function __construct($url, $displayname = null, $getctag = null ) {
        $this->url = $url;
        $this->displayname = $displayname;
        $this->getctag = $getctag;
        $this->is_default = FALSE;
        $this->order = FALSE;
        $this->rgba_color = FALSE;
        $this->shared = FALSE;
    }

    public function __toString() {
        return( '(URL: '.$this->url.'   Ctag: '.$this->getctag.'   Displayname: '.$this->displayname .')'. "\n" );
    }
}

