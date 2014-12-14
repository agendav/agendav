<?php

namespace AgenDAV\Event;

/*
 * Copyright 2014 Jorge López Pérez <jorge@adobo.org>
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
use AgenDAV\Event\VObjectEventInstance;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * VObject implementation of Events
 *
 */
class VObjectEvent implements Event
{
    protected $vcalendar;

    protected $is_recurrent;

    /**
     * @param mixed VCalendar $vcalendar
     */
    public function __construct(VCalendar $vcalendar)
    {
        $this->vcalendar = $vcalendar;
        $this->is_recurrent = $this->checkIfRecurrent();
    }

    public function isRecurrent()
    {
        return $this->is_recurrent;
    }

    public function expand(\DateTime $start, \DateTime $end, $url = null, $etag = null)
    {
        $expanded_vcalendar = clone $this->vcalendar;
        $expanded_vcalendar->expand($start, $end);

        $result = [];
        $rrule = null;

        if ($this->isRecurrent()) {
            $rrule = $this->vcalendar->VEVENT[0]->RRULE;
        }

        foreach ($expanded_vcalendar->VEVENT as $vevent) {
            if ($rrule !== null) {
                $vevent->RRULE = $rrule;
            }

            $result[] = new VObjectEventInstance($vevent, $url, $etag);
        }

        return $result;
    }

    protected function checkIfRecurrent()
    {
        $count = count($this->vcalendar->VEVENT);

        if ($count > 1) {
            return true;
        }

        $vevent_0 = $this->vcalendar->VEVENT[0];

        if (isset($vevent_0->RRULE)) {
            return true;
        }

        return false;
    }
}

