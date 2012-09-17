<?php 

namespace AgenDAV\Data;

if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

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
    public $type, $order = FALSE;
    public $is_absolute;
    public $before;
    public $qty, $interval;
    public $relatedStart;
    public $absdatetime, $tdate, $ttime;
    private $CI;

    public static $intervals = array(
            'week' => 10080,
            'day' => 1440,
            'hour' => 60,
            'min' => 1,
            );

    public function __construct() {
        // TODO add more types
        $this->type = 'DISPLAY';
        $this->CI =& get_instance();
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
        $this->relatedStart = $trigger['relatedStart'];
        $this->approx_trigger($trigger);
    }

    private function approx_trigger($trigger) {
        $minutes = 0;
        foreach (self::$intervals as $u => $m) {
            if (isset($trigger[$u])) {
                $minutes += $trigger[$u]*$m;
            }
        }

        if ($minutes == 0) {
            $use_unit = 'min';
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

    /**
     * Assigns the trigger, action and description for the given VALARM
     component
     */
    public function assign_properties(&$valarm) {
        if ($this->is_absolute) {
            $valarm->setProperty('trigger',
                    $this->CI->dates->datetime2idt($this->absdatetime),
                    array('VALUE' => 'DATE-TIME'));
        } else {
            $valarm->setProperty('trigger',
                    array(
                        $this->interval => $this->qty,
                        'relatedStart' => $this->relatedStart,
                        'before' => $this->before,
                        ));
        }

        $valarm->setProperty('action', $this->type);
        // TODO store description
        $valarm->setProperty('description', 'AgenDAV');

        log_message('INTERNALS', 'Returning VALARM ' .
                $valarm->createComponent($x));

        return $valarm;
    }


    public function __toString() {
        if ($this->is_absolute) {
            return 'R[' . $this->absdatetime->format('c') . ']';
        } else {
            return 'R[' . $this->qty . ' ' . $this->interval .
                ' ' . ($this->before ? 'before' : 'after') . 
                ' ' . ($this->relatedStart ? 'start' : 'end') . ']';
        }
    }
}
