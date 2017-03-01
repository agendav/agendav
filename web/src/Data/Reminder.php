<?php

namespace AgenDAV\Data;

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

use Sabre\VObject\Component\VAlarm;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject;

class Reminder
{
    /**
     * Stores the position for this reminder in the original
     * event
     *
     * @var integer
     */
    protected $position;

    /**
     * @var \DateInterval
     */
    protected $when;

    /**
     * @param \DateInterval $when
     * @param integer $position
     */
    public function __construct(\DateInterval $when, $position = null)
    {
        $this->when = $when;
        $this->position = $position;
    }

    /**
     * Parses an input array and returns a Reminder
     *
     * @param array $input Key-value based array. Expected keys are:
     *                     - count: number of <units>
     *                     - unit: one of minutes, hours or days
     *                     - position (optional)
     * @return AgenDAV\Data\Reminder
     */
    public static function createFromInput(array $input)
    {
        $string = $input['count'] . ' ' . $input['unit'];
        $interval = \DateInterval::createFromDateString($string);

        $position = !empty($input['position']) ? $input['position'] : null;

        return new self($interval, $position);
    }

    /**
     * Receives an VObject VALARM and creates a new Reminder.
     *
     * If the VALARM is not supported by AgenDAV, a null value will be returned
     *
     * @param \Sabre\VObject\Component\VAlarm $valarm
     * @param integer $position Position of this VALARM inside the VEVENT
     * @return AgenDAV\Data\Reminder|null
     */
    public static function createFromVObject(VAlarm $valarm, $position)
    {
        $trigger = $valarm->TRIGGER;
        $value = (string) $trigger['VALUE'];
        $related = (string) $trigger['RELATED'];
        if ($value === 'DATE-TIME' || $related === 'END') {
            return null;
        }

        try {
            $duration = VObject\DateTimeParser::parseDuration($trigger);
        } catch (\LogicException $exception) {
            // Ooops
            return null;
        }

        // Trigger *after* DTSTART
        if ($duration->invert !== 1 && self::countMinutes($duration) !== 0) {
            return null;
        }

        $duration->invert = 0;

        return new self($duration, $position);
    }

    /**
     * Parses current date interval
     *
     * @return array [count, unit]
     */
    public function getParsedWhen()
    {
        $count_minutes = self::countMinutes($this->when);

        if ($count_minutes === 0) {
            return [ 0, 'minutes' ];
        }

        $units = [
            'months' => 40320,
            'weeks' => 10080,
            'days' => 1440,
            'hours' => 60,
            'minutes' => 1,
        ];

        foreach ($units as $unit => $minutes) {
            if ($count_minutes % $minutes === 0) {
                $count = $count_minutes/$minutes;
                return [
                    $count,
                    $unit
                ];
            }
        }

        // What happened?
        return [99999, 'months'];
    }

    /*
     * Getter for position
     *
     * @return int|null
     */
    public function getPosition()
    {
        return $this->position;
    }

    /*
     * Setter for position
     *
     * @param int|null $position
     */
    public function setPosition($position = null)
    {
        $this->position = $position;
    }

    /*
     * Getter for when
     *
     * @return \DateInterval
     */
    public function getWhen()
    {
        return $this->when;
    }

    /**
     * Returns an ISO8601 representation of current $when
     *
     * @return string
     */
    public function getISO8601String()
    {
        list($count, $unit) = $this->getParsedWhen();

        $template = '';

        // Generate a template for result format
        switch($unit) {
            case 'months':
                $count *= 28;
                $template = '%dD';
                break;
            case 'weeks':
                $count *= 7;
                $template = '%dD';
                break;
            case 'days':
                $template = '%dD';
                break;
            case 'hours':
                $template = 'T%dH';
                break;
            case 'minutes':
                $template = 'T%dM';
                break;
        }

        $format = '-P' . $template;

        return sprintf($format, $count);
    }

    /**
     * Counts the minutes of a \DateInterval
     *
     * @return int
     */
    public static function countMinutes(\DateInterval $dateinterval)
    {
        $dateinterval_units = [
            'i' => 1,
            'h' => 60,
            'd' => 1440,
            'm' => 40320,
        ];


        $count_minutes = 0;

        foreach ($dateinterval_units as $key => $minutes) {
            if ($dateinterval->{$key} !== 0) {
                $count_minutes = $dateinterval->{$key} * $minutes;
                break;
            }
        }

        return $count_minutes;
    }
}
