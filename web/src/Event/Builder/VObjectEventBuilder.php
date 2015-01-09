<?php

namespace AgenDAV\Event\Builder;

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

use AgenDAV\Uuid;
use AgenDAV\Event;
use AgenDAV\Event\VObjectEvent;
use Sabre\VObject\Component\VCalendar;

/**
 * Class used to generate new VObjectEvents
 */
class VObjectEventBuilder implements EventBuilder
{
    /**
     * Creates an empty Event object
     *
     * @param string $uid Optional UID for this event
     * @return \AgenDAV\Event
     */
    public function create($uid = null)
    {
        $vcalendar = new VCalendar();

        if ($uid === null) {
            $uid = \AgenDAV\Uuid::generate();
        }

        $event = new VObjectEvent($vcalendar);
        $event->setUid($uid);

        return $event;
    }
}
