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
                'fullcalendar' => 'HH:mm',
                ),
            '12' => array(
                'strftime' => '%l:%M',  // %P will be simulated, not working
                                        // in all systems
                'date' => 'h:i A', // Match timepicker format
                'fullcalendar' => 'h(:mm)tt',
                ));

    // Possible date formats (not needed for strftime)
    private static $date_formats = array(
            'ymd' => array(
                'date' => 'Y-m-d',
                'datepicker' => 'yy-mm-dd',
                'strftime' => '%Y-%m-%d',
                ),
            'dmy' => array(
                'date' => 'd/m/Y',
                'datepicker' => 'dd/mm/yy',
                'strftime' => '%d/%m/%Y',
                ),
            'mdy' => array(
                'date' => 'm/d/Y',
                'datepicker' => 'mm/dd/yy',
                'strftime' => '%m/%d/%Y',
                ),
            );

    /**
     * Returns a time format string
     * 
     * @param string $type One of: fullcalendar, date or strftime
     * @param string $which_one One of the predefined sets: 12 or 24
     * @static
     * @access private
     * @return string Format string
     * @throws \InvalidArgumentException
     */
    private static function getTimeFormatFor($type, $which_one)
    {
        if ($which_one != '12' && $which_one != '24') {
            throw new \InvalidArgumentException('Invalid type ' . $type);
        }

        switch($type) {
            case 'fullcalendar':
            case 'date':
            case 'strftime':
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
     * @access private
     * @return string Format string
     * @throws \InvalidArgumentException
     */
    private static function getDateFormatFor($type, $which_one)
    {
        if ($which_one != 'ymd' && $which_one != 'dmy' && $which_one != 'mdy') {
            throw new \InvalidArgumentException('Invalid type ' . $type);
        }

        switch($type) {
            case 'fullcalendar':
            case 'date':
            case 'strftime':
                return self::$date_formats[$which_one][$type];
                break;
            default:
                throw new \InvalidArgumentException('Invalid type ' . $type);
                break;
        }
    }

    /**
     * createDateTime 
     * 
     * @param string $format 
     * @param string $str 
     * @param \DateTimeZone $tz 
     * @static
     * @access public
     * @return \DateTime
     * @throws \InvalidArgumentException
     */
    public static function createDateTime($format, $str, \DateTimeZone $tz)
    {
        $dt = \DateTime::createFromFormat($format, $str, $tz);

        // Check for errors
        $err = \DateTime::getLastErrors();

        if (false === $dt || $err['warning_count']>0) {
            throw new \InvalidArgumentException('Error building DateTime object');
        }

        return $dt;
    }


    /**
     * Returns a DatTime object with its timestamp rounded to the nearest multiple of specified factor,
     * using the provided base time
     *
     * @param \DateTime $base Base date and time to start looking for the rounded time
     * @param int $factor Rounding factor, specified in seconds
     * @access public
     * @return \DateTime Resulting DateTime object
     * @throws \InvalidArgumentException
     */
    public static function approximate($base, $factor = 1800)
    {
        if (!($base instanceof \DateTime)) {
            throw new \InvalidArgumentException('approximate() called with no base time');
        }

        if (!is_int($factor)) {
            throw new \InvalidArgumentException('approximate() called with invalid factor');
        }

        $rounded_datetime = clone $base;
        $ts = $base->getTimestamp();
        $rounded_datetime->setTimestamp(round(($ts/$factor))*$factor);

        return $rounded_datetime;
    }

    /**
     * Creates a DateTime object from a string formatted by frontend (such as
     * m/d/Y H:i).
     *
     * @param string $str String coming from frontend
     * @param string $date_format Expected date format
     * @param string $time_format Expected time format
     * @param \DateTimeZone $tz Timezone to use
     * @access public
     * @return \DateTime Date and time parsed from initial string
     * @throws \InvalidArgumentException
     */
    public static function frontEndToDateTime($str, $date_format, $time_format, \DateTimeZone $tz)
    {
        $format = self::getDateFormatFor('date', $date_format) 
            . ' '
            .  self::getTimeFormatFor('date', $time_format);

        return self::createDateTime($format, $str, $tz);
    }

    /**
     * Creates a DateTime object from a date formatted by Fullcalendar
     * events: yyyymmddHHii).
     *
     * @param string $str 
     * @param \DateTimeZone $tz 
     * @static
     * @access public
     * @return \DateTime
     * @throws \InvalidArgumentException
     */
    public static function fullcalendarToDateTime($str, \DateTimeZone $tz)
    {
        $format = 'YmdHis';
        $dt = self::createDateTime($format, $str, $tz);

        return $dt;
    }

    /**
     * Converts a DateTime object to iCalendar DATE-TIME/DATE format
     * 
     * @param \DateTime $dt 
     * @param string $type DATE-TIME or DATE 
     * @access public
     * @return void
     */
    public static function dateTimeToiCalendar(\DateTime $dt, $type)
    {
        $format = 'Ymd';

        if ($type == 'DATE-TIME') {
            $format .= '\THis';
        }

        $tz = $dt->getTimeZone();

        if ($tz->getName() == 'UTC' && $type != 'DATE') {
            $format .= '\Z';
        }

        return $dt->format($format);
    }

    /**
     * Converts a DATE-TIME/DATE array from iCalcreator to a \DateTime object
     *
     * @param Array $id_arr Array from iCalcreator (year, month, day, etc)
     * @param \DateTimeZone $tz Timezone for given input
     * @access public
     * @return \DateTime
     */
    public function iCalcreatorToDateTime($icalcreator_data, \DateTimeZone $tz)
    {
        $format = 'YmdHis';

        // $tz should be enough
        unset($icalcreator_data['tz']);

        $str = '';
        foreach ($icalcreator_data as $k => $v) {
            $str .= $v;
        }

        // VALUE=DATE
        if (!isset($icalcreator_data['hour'])) {
            $str .= '000000';
        }

        return self::createDateTime($format, $str, $tz);
    }

    /**
     * Converts a DateInterval to a DURATION string
     *
     * Parameter has to be the result of add() or diff() to an existing
     * DateTime object
     */
    function di2duration($di) {
        if ($obj->days === FALSE) {
            // We have a problem
            return FALSE;
        }

        $days = $obj->days;
        $seconds = $obj->s + $obj->i*60 + $obj->h*3600;
        $str = '';

        // Simplest case
        if ($days%7 == 0 && $seconds == 0) {
            $str = ($days/7) . 'W';
        } else {
            $time_units = array(
                    '3600' => 'H',
                    '60' => 'M',
                    '1' => 'S',
                    );
            $str_time = '';
            foreach ($time_units as $k => $v) {
                if ($seconds >= $k) {
                    $str_time .= floor($seconds/$k) . $v;
                    $seconds %= $k;
                }
            }

            // No days
            if ($days == 0) {
                $str = 'T' . $str_time;
            } else {
                $str = $days . 'D' . (empty($str_time) ? '' : 'T' . $str_time);
            }
        }

        return ($obj->invert == '1' ? '-' : '') . 'P' . $str;
    }

    /**
     * Convertes a DURATION string to a DateInterval
     * Allows the use of '-' in front of the string
     */
    function duration2di($str) {
        $minus;
        $new_str = preg_replace('/^(-)/', '', $str, -1, $minus);

        $interval = new DateInterval($new_str);
        if ($minus == 1) {
            $interval->invert = 1;
        }

        return $interval;
    }

    /**
     * Converts a X-CURRENT-DTSTART/X-CURRENT-DTEND string to a DateTime
     * object
     */
    function x_current2datetime($str, $tz) {
        $matches = array();
        $res = preg_match('/^(\d+)-(\d+)-(\d+)( (\d+):(\d+):(\d+)( (\S+))?)?$/', $str, $matches);

        if ($res === FALSE || $res != 1) {
            log_message('ERROR',
                    'Error processing [' . $str . '] as X-CURRENT-*'
                    .' string');
            return new DateTime();
        }

        $y = $matches[1];
        $m = $matches[2];
        $d = $matches[3];
        $h = isset($matches[5]) ? $matches[5] : '00';
        $i = isset($matches[6]) ? $matches[6] : '00';
        $s = isset($matches[7]) ? $matches[7] : '00';
        // Timezone is ignored, we already have $tz
        //$e = isset($matches[9]) ? $matches[9] : $tz;
    
        $format = 'dmY His';
        $new_str = $d.$m.$y.' '.$h.$i.$s;

        $dt = $this->create_datetime($format, $new_str, $tz);

        if ($dt === FALSE) {
            $this->CI->extended_logs->message('ERROR',
                    'Error processing ' . $new_str . ' (post) as a string'
                    .' for X-CURRENT-*');
            return new DateTime();
        }

        return $dt;
    }


    /**
     * Formats a timestamp time using strftime
     *
     * @param   int Timestamp
     * @param   DateTime    DateTime object (used to calculate am/pm)
     * @return  string  Formatted time string with strftime
     */
    function strftime_time($timestamp, $dt) {
        $format = Dates::$time_formats[$this->cfg_time]['strftime'];
        $result = strftime($format, $timestamp);
        if ($this->cfg_time == '12') {
            $result .= $dt->format('a');
        }
        
        return $result;
    }


}
