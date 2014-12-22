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

use AgenDAV\Event;
use AgenDAV\EventInstance;
use AgenDAV\Event\VObjectEvent;
use AgenDAV\Event\VObjectEventInstance;
use Sabre\VObject\Component\VEvent;

/**
 * Class used to generate new VObjectEventInstances
 */
class VObjectEventInstanceBuilder implements EventInstanceBuilder
{
    /**
     * Creates an empty EventInstance object
     *
     * @param \AgenDAV\Event $event Event this instance will be attached to
     * @return \AgenDAV\EventInstance
     * @throws \LogicException If $event has no UID assigned
     */
    public function createFor(\AgenDAV\Event $event)
    {
        $result = $event->createEventInstance();

        return $result;
    }

    /**
     * Creates an EventInstance object after receiving an array of properties
     * with the following keys:
     *
     * summary
     * location
     * start_date
     * start_time
     * end_date
     * end_time
     * allday
     * description
     * class
     * transp
     * TODO: recurrence rules, reminders, recurrence-id
     *
     * @param \AgenDAV\Event $event Parent event
     * @param array $attributes
     * @return \AgenDAV\EventInstance
     */
    public function createFromInput(\AgenDAV\Event $event, array $attributes)
    {
        $instance = $this->createFor($event);
        foreach ($attributes as $key => $value) {
            $this->assignProperty($instance, $key, $value);
        }

        // TODO: set DTSTART and DTEND
        /*
            case 'start_date':
            case 'start_time':
            case 'end_date':
            case 'end_time':
            case 'allday':
         */

        return $instance;
    }


    protected function assignProperty(
        \AgenDAV\Event\VObjectEventInstance $instance,
        $key,
        $value
    )
    {
        switch ($key) {
            case 'summary':
                $instance->setSummary($value);
                break;
            case 'location':
                $instance->setLocation($value);
                break;
            case 'description':
                $instance->setDescription($value);
                break;
            case 'class':
                $instance->setClass($value);
                break;
            case 'transp':
                $instance->setTransp($value);
                break;
        }
    }
}
