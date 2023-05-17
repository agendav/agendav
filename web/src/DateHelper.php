<?php

namespace AgenDAV;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
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
 * This class parses and generates date and time formats 
 */
class DateHelper
{
    /**
     * Creates a new \DateTimeImmutable object using a value, a format string and a
     * timezone
     *
     * @param string $format DateTime format (see http://php.net/manual/en/datetime.createfromformat.php)
     * @param string $value Input value that has to match the format above 
     * @param \DateTimeZone $timezone Time zone the resulting \DateTimeImmutable will be generated
     * @return \DateTimeImmutable
     * @throws \InvalidArgumentException
     */
    public static function createDateTime($format, $value, \DateTimeZone $timezone)
    {
        $result = \DateTimeImmutable::createFromFormat($format, $value, $timezone);

        // Check for errors
        $err = \DateTimeImmutable::getLastErrors();

        if (false === $result || $err['warning_count']>0) {
            throw new \InvalidArgumentException('Error building DateTimeImmutable object');
        }

        return $result;
    }

    /**
     * Creates a DateTime object from a ISO8601 string coming from the
     * frontend
     *
     * @param string $str String coming from frontend
     * @param \DateTimeZone $tz Timezone to use
     * @access public
     * @return \DateTimeImmutable Date and time parsed from initial string
     * @throws \InvalidArgumentException
     */
    public static function frontEndToDateTime($str, \DateTimeZone $tz = null)
    {
        $format = 'Y-m-d\TH:i:s.u\Z';

        $result = self::createDateTime($format, $str, new \DateTimeZone('UTC'));
        if ($tz !== null) {
            $result = $result->setTimezone($tz);
        }

        return $result;
    }

    /**
     * Creates a DateTime object from a date formatted by FullCalendar
     * events
     *
     * @param string $input String provided by FullCalendar
     * @param \DateTimeZone $timezone User timezone
     * @return \DateTimeImmutable Using the provided timezone
     * @throws \InvalidArgumentException
     */
    public static function fullcalendarToDateTime($input, \DateTimeZone $timezone)
    {
        // depending of the view type Month, Week, Day the format sent by fullcalendar is Y-m-d or Y-m-d\TH:i:s
        if (strpos($input, 'T') == 10) {
            $format = 'Y-m-d\TH:i:s';
        } else {
            $format = 'Y-m-d\THis';
            $input .= 'T000000';
        }

        $result = self::createDateTime($format, $input, $timezone);

        return $result;
    }

    /**
     * Adds (or subtracts) an amount of minutes to a \DateTime object.
     * Modifies original object
     *
     * @param \DateTimeImmutable $datetime
     * @param string|int $minutes
     * @return \DateTimeImmutable
     */
    public static function addMinutesTo(\DateTimeImmutable $datetime, $minutes)
    {
        return $datetime->modify($minutes . ' minutes');
    }

    /**
     * Returns a new \DateTimeImmutable based on an existing \DateTimeImmutable, with a different
     * timezone but keeping the same date and time.
     *
     * @param \DateTimeImmutable $datetime
     * @param \DateTimeZone $timezone
     * @return \DateTimeImmutable
     */
    public static function switchTimeZone(\DateTimeImmutable $datetime, \DateTimeZone $timezone)
    {
        $result = self::createDateTime('YmdHis', $datetime->format('YmdHis'), $timezone);

        return $result;
    }

    /**
     * Converts a \DateTimeImmutable from a timed event to a \DateTimeImmutable in UTC starting at
     * 0:00:00 on the same date as the original one
     *
     * @param \DateTimeImmutable $datetime
     * @return \DateTimeImmutable
     */
    public static function getStartOfDayUTC(\DateTimeImmutable $datetime)
    {
        $result = $datetime->setTime(0, 0, 0);
        $result = self::switchTimeZone($result, new \DateTimeZone('UTC'));

        return $result;
    }

    /**
     * Returns an array of timezone names
     *
     * @return array (key = value)
     */
    public static function getAllTimeZones()
    {
        $timezones = \DateTimeZone::listIdentifiers();

        // Builds a new array using timezone identifiers as both key and value
        return array_combine($timezones, $timezones);
    }

}
