<?php

namespace AgenDAV\Event\Parser;

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
use AgenDAV\Event\Parser;
use AgenDAV\Event\VObjectEvent;
use Sabre\VObject\Reader;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ParseException;

/**
 * Parses events using Sabre VObject
 */
class VObjectParser implements Parser
{
    /**
     * Parses an iCalendar text resource
     *
     * @param string $icalendar
     * @return AgenDAV\Event
     * @throws \UnexpectedValueException If $icalendar is not a valid iCalendar
     *                                   resource
     */
    public function parse($icalendar)
    {
        try {
            $vcalendar = Reader::read($icalendar, Reader::OPTION_FORGIVING);
        } catch (ParseException $exception) {
            throw new \UnexpectedValueException($exception->getMessage());
        }

        $event = new VObjectEvent($vcalendar);

        return $event;
    }

}

