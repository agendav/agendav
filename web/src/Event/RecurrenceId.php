<?php

namespace AgenDAV\Event;

/*
 * Copyright 2015 Jorge López Pérez <jorge@adobo.org>
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

use Sabre\VObject\DateTimeParser;

/**
 * Recurrence-Id wrapper class
 *
 */
class RecurrenceId
{
    /** @var \DateTime */
    protected $datetime;

    /**
     * Builds a new RECURRENCE-ID. Stores the \DateTime object using UTC
     *
     * @param \DateTime $datetime
     */
    public function __construct(\DateTime $datetime)
    {
        $this->datetime = clone $datetime;
        $this->datetime->setTimeZone(new \DateTimeZone('UTC'));
    }

    /**
     * Creates a new RecurrenceId from an iCalendar string
     *
     * @param string $recurrence_id_string
     * @return \AgenDAV\Event\RecurrenceId
     */
    public static function buildFromString($recurrence_id_string)
    {
        $datetime = DateTimeParser::parse($recurrence_id_string, null); // UTC

        return new self($datetime);
    }

    /*
     * Getter for datetime
     *
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->datetime;
    }

    /**
     * Returns an iCalendar formatted string in UTC
     *
     * @param bool $all_day If true, resulting string will be a DATE value
     * @return string
     */
    public function getString($all_day = false)
    {
        if ($all_day) {
            return $this->datetime->format('Ymd');
        }

        return $this->datetime->format('Ymd\THis\Z');
    }

    /**
     * Checks if a given \DateTime object matches this
     * recurrence instance.
     *
     * Time zones are ignored, as the equals (==) operator for
     * \DateTime objects behaves like that.
     *
     * @param \DateTime $datetime
     * @return bool true if they match, false otherwise
     */
    public function matchesDateTime(\DateTime $datetime)
    {
        return $this->datetime == $datetime;
    }
}

