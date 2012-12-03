<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

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

class Dates {

    // Possible time formats
    static $timeformats = array(
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
    static $dateformats = array(
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

    private $CI;
    private $cfg_time;
    private $cfg_date;

    function __construct() {
        $this->CI =& get_instance();

        // Load time and date formats
        $cfg_time = $this->CI->config->item('default_time_format');
        if ($cfg_time === FALSE 
                || ($cfg_time != '12' && $cfg_time != '24')) {
            log_message('ERROR', 
                    'Invalid default_time_format configuration value');
            $this->cfg_time = '24';
        } else {
            $this->cfg_time = $cfg_time;
        }

        $cfg_date = $this->CI->config->item('default_date_format');
        if ($cfg_date === FALSE 
                || ($cfg_date != 'ymd' && $cfg_date != 'dmy'
                    && $cfg_date != 'mdy')) {
            log_message('ERROR', 
                    'Invalid default_date_format configuration value');
            $this->cfg_date = 'ymd';
        }  else {
            $this->cfg_date = $cfg_date;
        }

    }

    /**
     * Returns a DatTime object with date approximated by factor seconds.
     * Defaults to 30 minutes (60*30 = 1800)
     */

    function approx_by_factor($time = null, $tz = null, $factor = 1800) {
        if (is_null($time)) {
            $time = time();
        }

        if (is_null($tz)) {
            $tz =
                $this->CI->timezonemanager->getTz(
                        $this->CI->config->item('default_timezone'));
        }

        $rounded = (round($time/$factor))*$factor;

        return $this->ts2datetime($rounded, $tz);
    }

    /**
     * Creates a DateTime object from an UNIX timestamp using the specified
     * Timezone. If no TZ is specified then default one is used
     */
    function ts2datetime($ts, $tz = null) {
        if (is_null($tz)) {
            $tz = $this->CI->timezonemanager->getTz(
                    $this->CI->config->item('default_timezone'));
        }

        $obj = new DateTime('@' . $ts);
        // When creating by timestamp, DateTime ignores current timezone
        $obj->setTimeZone($tz);

        return $obj;
    }

    /**
     * Creates a DateTime object from a date formatted by frontend (such as
     * m/d/Y H:i).
     *
     * Returns FALSE on date parsing error
     */
    function frontend2datetime($str, $tz = null) {
        if (is_null($tz)) {
            $tz = $this->CI->timezonemanager->getTz(
                    $this->CI->config->item('default_timezone'));
        }

        $format = $this->date_format_string('date') . ' '.
            $this->time_format_string('date');

        $obj = $this->create_datetime($format, $str, $tz);

        return $obj;
    }

    /**
     * Creates a DateTime object from a date formatted by Fullcalendar
     * events: yyyymmddHHii).
     *
     * Returns FALSE on date parsing error
     */
    function fullcalendar2datetime($str, $tz = null) {
        if (is_null($tz)) {
            $tz = $this->CI->timezonemanager->getTz(
                    $this->CI->config->item('default_timezone'));
        }

        $format = 'YmdHis';

        $obj = $this->create_datetime($format, $str, $tz);

        return $obj;
    }

    /**
     * Converts a DateTime to DATE-TIME format 
     * in UTC time by default
     *
     * If no object is passed, current time is used
     */
    function datetime2idt($dt = null, $tz = null, $format = '') {

        if (is_null($tz)) {
            $tz = $this->CI->timezonemanager->getTz('UTC');
        }

        if (is_null($dt)) {
            $dt = new DateTime('now', $tz);
        } else {
            $dt->setTimeZone($tz);
        }

        if (empty($format)) {
            $format = $this->format_for('DATE-TIME', $tz);
        }

        $str = $dt->format($format);

        return $str;
    }

    /**
     * Converts a DATE-TIME/DATE formatted string to DateTime
     * in UTC time by default.
     *
     * Default timezone is used if not specified
     */
    function idt2datetime($id_arr, $tz = null) {
        if (is_null($tz)) {
            $tz = $this->CI->timezonemanager->getTz('UTC');
        }

        $format = 'YmdHis';

        // $tz should be enough
        unset($id_arr['tz']);

        $str = '';
        foreach ($id_arr as $k => $v) {
            $str .= $v;
        }

        // VALUE=DATE
        if (!isset($id_arr['hour'])) {
            $str .= '000000';
        }

        $obj = $this->create_datetime($format, $str, $tz);

        return $obj;
    }

    /**
     * Returns a suitable date() format string according to specified
     * timezone to parse a DATE-TIME/DATE iCalendar string.
     *
     * Defaults to UTC
     */

    function format_for($type = 'DATE-TIME', $tz = null) {
        $format = '';

        if (is_null($tz)) {
            $tz = $this->CI->timezonemanager->getTz('UTC');
        }

        if ($type == 'DATE') {
            $format = 'Ymd';
        } else {
            $format = 'Ymd\THis';
        }

        if ($tz->getName() == 'UTC' && $type != 'DATE') {
            $format .= '\Z';
        }

        return $format;
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
            log_message('ERROR',
                    'Error processing ' . $new_str . ' (post) as a string'
                    .' for X-CURRENT-*');
            return new DateTime();
        }

        return $dt;
    }

    /**
     * Returns a time format string for the current user
     *
     * @param   string  Type of format (fullcalendar, date, strftime)
     * @return  string  Format string. Default formats on invalid params
     */
    function time_format_string($type) {
        switch($type) {
            case 'fullcalendar':
            case 'date':
            case 'strftime':
                return Dates::$timeformats[$this->cfg_time][$type];
                break;
            default:
                log_message('ERROR', 
                        'Invalid type for time_format_string() passed'
                        .' ('.$type.')');
                break;
        }
    }

    /**
     * Returns a date format string for the current user
     *
     * @param   string  Type of format (date, datepicker)
     * @return  string  Format string. Default formats on invalid params
     */
    function date_format_string($type) {
        switch($type) {
            case 'date':
            case 'datepicker':
            case 'strftime':
                return Dates::$dateformats[$this->cfg_date][$type];
                break;
            default:
                log_message('ERROR', 
                        'Invalid type for date_format_string() passed'
                        .' ('.$type.')');
                break;
        }

    }


    /**
     * Formats a timestamp time using strftime
     *
     * @param   int Timestamp
     * @param   DateTime    DateTime object (used to calculate am/pm)
     * @return  string  Formatted time string with strftime
     */
    function strftime_time($timestamp, $dt) {
        $format = Dates::$timeformats[$this->cfg_time]['strftime'];
        $result = strftime($format, $timestamp);
        if ($this->cfg_time == '12') {
            $result .= $dt->format('a');
        }
        
        return $result;
    }

    /**
     * Returns a DateTime object using createFromFormat
     *
     * @param   string  Format used to parse the given string
     * @param   string  String that contains date-time
     * @param   DateTimeZone    Timezone
     * @return  DateTime/boolean    FALSE on error
     */
    function create_datetime($format, $str, $tz) {
        $dt = DateTime::createFromFormat($format, $str, $tz);

        // Check for errors
        $err = DateTime::getLastErrors();

        if (FALSE === $dt || $err['warning_count']>0) {
            $dt = FALSE;
        }

        return $dt;
    }

}
