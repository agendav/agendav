<?php

namespace AgenDAV\Data;

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

use AgenDAV\DateHelper;

class Recurrence
{
    /**
     * Frequency (DAILY, WEEKLY, MONTHLY, YEARLY)
     * @var string
     */
    protected $frequency;

    /**
     * Date this recurrence finishes
     * @var \DateTime
     */
    protected $until;

    /**
     * Number of times the event repeats
     * @var integer
     */
    protected $count;

    /**
     * Interval of repetitions
     * @var integer
     */
    protected $interval;

    /**
     * Creates a new recurrence object
     *
     * @param string $frequency
     */
    public function __construct($frequency)
    {
        $this->setFrequency($frequency);
        $this->interval = 1;
        $this->until = null;
        $this->count = null;
    }

    /*
     * Getter for frequency
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /*
     * Setter for frequency
     *
     * @param string $frequency
     * @throws \InvalidArgumentException if an unsupported frequency is passed
     */
    public function setFrequency($frequency)
    {
        $frequency = strtoupper($frequency);

        switch ($frequency) {
            case 'DAILY':
            case 'WEEKLY':
            case 'MONTHLY':
            case 'YEARLY':
                $this->frequency = $frequency;
                break;
            default:
                throw new \InvalidArgumentException(
                    $frequency . ' frequency is not supported by Recurrence class'
                );
        }
    }

    /*
     * Getter for until
     */
    public function getUntil()
    {
        return $this->until;
    }

    /*
     * Setter for until
     *
     * If you want to remove the current UNTIL parameter, just set it to null
     *
     * @param \DateTime|null $until
     * @throws \LogicException if UNTIL and COUNT are both defined
     */
    public function setUntil($until)
    {
        if ($until === null) {
            $this->until = null;
            return;
        }

        if ($this->count !== null) {
            throw new \LogicException('UNTIL and COUNT cannot be both set');
        }

        $this->until = $until;
    }

    /*
     * Getter for count
     */
    public function getCount()
    {
        return $this->count;
    }

    /*
     * Setter for count
     *
     * If you want to remove the current COUNT parameter, just set it to null
     *
     * @param integer|null $count
     * @throws \LogicException if UNTIL and COUNT are both defined
     * @throws \InvalidArgumentException if $count is 0 or negative
     */
    public function setCount($count)
    {
        if ($count === null) {
            $this->count = null;
            return;
        }

        if ($this->until !== null) {
            throw new \LogicException('UNTIL and COUNT cannot be both set');
        }

        $count = (int)$count;
        if ($count <= 0) {
            throw new \InvalidArgumentException('UNTIL and COUNT cannot be both set');
        }

        $this->count = $count;
    }

    /*
     * Getter for interval
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /*
     * Setter for interval
     *
     * @param integer $interval
     * @throws \InvalidArgumentException if $integer is 0 or negative
     */
    public function setInterval($interval)
    {
        $interval = (int)$interval;

        if ($interval <= 0) {
            throw new \InvalidArgumentException('Interval cannot be <= 0');
        }

        $this->interval = $interval;
    }

    /**
     * Generates a RRULE data array for iCalcreator
     *
     * @param string $type One of DATE-TIME/DATE
     * @return array
     */
    public function generateiCalcreatorData($type = 'DATE-TIME')
    {
        $result = [];

        $result['FREQ'] = $this->frequency;
        if ($this->interval != 1) {
            $result['INTERVAL'] = $this->interval;
        }

        if ($this->count !== null) {
            $result['COUNT'] = $this->count;
        }

        if ($this->until !== null) {
            $result['UNTIL'] = DateHelper::dateTimeToiCalendar($this->until, $type);
        }

        return $result;
    }

    /**
     * Generates a new Recurrence with the arguments received on the
     * input array
     *
     * Valid keys for the array are:
     *
     *  'frequency' (DAILY, WEEKLY, MONTHLY or YEARLY)
     *  'until' (ISO8601 datetime coming from the frontend)
     *  'count' (integer or string containing a number)
     *  'interval' (integer or string containing a number)
     *
     *  @param array $input An associative array with the above keys
     *  @throws \InvalidArgumentException|\LogicException (see setCount and setUntil)
     *  @return \AgenDAV\Data\Recurrence
     */
    public static function createFromInput(array $input)
    {
        if (empty($input['frequency'])) {
            throw new \InvalidArgumentException(
                'Input data does not contain a frequency key'
            );
        }

        $recurrence = new Recurrence($input['frequency']);

        if (!empty($input['interval'])) {
            $recurrence->setInterval($input['interval']);
        }

        if (!empty($input['count'])) {
            $recurrence->setCount($input['count']);
        }

        if (!empty($input['until'])) {
            $until_date = DateHelper::frontEndToDateTime($input['until']);
            $recurrence->setUntil($until_date);
        }

        return $recurrence;
    }

    /**
     * Generates a new Recurrence with the arguments received on the
     * input array From iCalcreator
     *
     *  @param array $parts An array from iCalcreator describing a RRULE
     *  @throws \InvalidArgumentException|\LogicException (see setCount and setUntil)
     *  @return \AgenDAV\Data\Recurrence
     */
    public static function createFromiCalcreator(array $parts)
    {
        $data = [];
        $names = [
            'FREQ' => 'frequency',
            'INTERVAL' => 'interval',
            'COUNT' => 'count',
        ];

        foreach ($names as $original => $new_name) {
            if (!empty($parts[$original])) {
                $data[$new_name] = $parts[$original];
            }
        }


        $result = self::createFromInput($data);

        if (!empty($parts['UNTIL'])) {
            $until = DateHelper::iCalcreatorToDateTime(
                $parts['UNTIL'],
                new \DateTimeZone('UTC')
            );
            $result->setUntil($until);
        }

        return $result;
    }
}
