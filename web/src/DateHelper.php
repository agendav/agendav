<?php

namespace AgenDAV;

/*
 * Copyright 2011-2012 Jorge López Pérez <jorge@adobo.org>
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
    // Possible time formats
    private static $time_formats = array(
            '24' => array(
                'strftime' => '%H:%M',
                'date' => 'H:i',
                'fullcalendar' => 'H:mm',
                'moment' => 'HH:mm',
                ),
            '12' => array(
                'strftime' => '%l:%M',  // %P will be simulated, not working
                                        // in all systems
                'date' => 'h:i a', // Match timepicker format
                'fullcalendar' => 'h(:mm)a',
                'moment' => 'hh:mm A',
                ));

    // Possible date formats (not needed for strftime)
    private static $date_formats = array(
            'ymd' => array(
                'date' => 'Y-m-d',
                'datepicker' => 'yy-mm-dd',
                'strftime' => '%Y-%m-%d',
                'moment' => 'YYYY-MM-DD',
                ),
            'dmy' => array(
                'date' => 'd/m/Y',
                'datepicker' => 'dd/mm/yy',
                'strftime' => '%d/%m/%Y',
                'moment' => 'DD/MM/YYYY',
                ),
            'mdy' => array(
                'date' => 'm/d/Y',
                'datepicker' => 'mm/dd/yy',
                'strftime' => '%m/%d/%Y',
                'moment' => 'MM/DD/YYYY',
                ),
            );

    /**
     * Returns a time format string
     * 
     * @param string $type One of: fullcalendar, date or strftime
     * @param string $which_one One of the predefined sets: 12 or 24
     * @static
     * @access public
     * @return string Format string
     * @throws \InvalidArgumentException
     */
    public static function getTimeFormatFor($type, $which_one)
    {
        if ($which_one != '12' && $which_one != '24') {
            throw new \InvalidArgumentException('Invalid subtype ' . $which_one);
        }

        switch($type) {
            case 'fullcalendar':
            case 'date':
            case 'strftime':
            case 'moment':
                return self::$time_formats[$which_one][$type];
                break;
            default:
                throw new \InvalidArgumentException('Invalid type ' . $type);
                break;
        }
    }

    /**
     * Returns a date format string
     * 
     * @param string $type One of: fullcalendar, date or strftime
     * @param string $which_one One of the predefined sets: ymd, dmy, mdy
     * @static
     * @access public
     * @return string Format string
     * @throws \InvalidArgumentException
     */
    public static function getDateFormatFor($type, $which_one)
    {
        if ($which_one != 'ymd' && $which_one != 'dmy' && $which_one != 'mdy') {
            throw new \InvalidArgumentException('Invalid subtype ' . $which_one);
        }

        switch($type) {
            case 'date':
            case 'datepicker':
            case 'strftime':
            case 'moment':
                return self::$date_formats[$which_one][$type];
                break;
            default:
                throw new \InvalidArgumentException('Invalid type ' . $type);
                break;
        }
    }

    /**
     * Creates a new \DateTime object using a value, a format string and a
     * timezone
     *
     * @param string $format \DateTime format (see http://php.net/manual/en/datetime.createfromformat.php)
     * @param string $value Input value that has to match the format above 
     * @param \DateTimeZone $timezone Time zone the resulting \DateTime will be generated
     * @return \DateTime
     * @throws \InvalidArgumentException
     */
    public static function createDateTime($format, $value, \DateTimeZone $timezone)
    {
        $result = \DateTime::createFromFormat($format, $value, $timezone);

        // Check for errors
        $err = \DateTime::getLastErrors();

        if (false === $result || $err['warning_count']>0) {
            throw new \InvalidArgumentException('Error building DateTime object');
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
     * @return \DateTime Date and time parsed from initial string
     * @throws \InvalidArgumentException
     */
    public static function frontEndToDateTime($str, \DateTimeZone $tz = null)
    {
        $format = 'Y-m-d\TH:i:s.u\Z';

        $result = self::createDateTime($format, $str, new \DateTimeZone('UTC'));
        if ($tz !== null) {
            $result->setTimeZone($tz);
        }

        return $result;
    }

    /**
     * Creates a DateTime object from a date formatted by FullCalendar
     * events
     *
     * @param string $input String provided by FullCalendar
     * @param \DateTimeZone $timezone User timezone
     * @return \DateTime Using the provided timezone
     * @throws \InvalidArgumentException
     */
    public static function fullcalendarToDateTime($input, \DateTimeZone $timezone)
    {
        $format = 'Y-m-d\THis';
        $input .= 'T000000';

        $result = self::createDateTime($format, $input, $timezone);

        return $result;
    }

    /**
     * Convertes a DURATION string to a DateInterval
     * Allows the use of '-' in front of the string
     *
     * @param string $str DURATION value
     * @access public
     * @return \DateInterval
     */
    public static function durationToDateInterval($str)
    {
        $invert = 0;

        if ($str[0] == '-') {
            $invert = 1;
            $str = substr($str, 1);
        }

        $interval = new \DateInterval($str);
        $interval->invert = $invert;

        return $interval;
    }
}
