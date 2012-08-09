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

class Reminder {
    public $type, $order;
    public $is_absolute;
    public $before;
    public $qty, $interval;
    public $absdatetime, $tdate, $ttime;

    public static $intervals = array(
            'weeks' => 10080,
            'days' => 1440,
            'hours' => 60,
            'minutes' => 1,
            );

    public static $icalendar_indexes = array(
            'week' => 10080,
            'day' => 1440,
            'hour' => 60,
            'min' => 1,
            );

    public function __construct() {
        // TODO add more types
        $this->type = 'DISPLAY';
    }

    public static function createFrom($when) {
        $new_reminder = new Reminder();
        if (is_array($when)) {
            // Related to start/end
            $new_reminder->is_absolute = FALSE;
            $new_reminder->parse_trigger($when);
        } else {
            // Absolute
            $new_reminder->is_absolute = TRUE;
            $new_reminder->absdatetime = $when;
        }

        return $new_reminder;
    }


    public function parse_trigger($trigger) {
        $this->before = $trigger['before'];
        $this->approx_trigger($trigger);
    }


    private function approx_trigger($trigger) {
        $minutes = 0;
        foreach (self::$icalendar_indexes as $u => $m) {
            if (isset($trigger[$u])) {
                $minutes += $trigger[$u]*$m;
            }
        }

        if ($minutes == 0) {
            $use_unit = 'minutes';
            // Fix 'before'
            $this->before = TRUE;
        } else {
            // Decide a measure
            $use_unit = '';
            foreach (self::$intervals as $unit => $q) {
                if ($minutes % $q == 0) {
                    $use_unit = $unit;
                    break;
                }
            }
        }

        $this->qty = $minutes/self::$intervals[$use_unit];
        $this->interval = $use_unit;
    }
}
