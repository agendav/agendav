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

use \AgenDAV\DateHelper;

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
    public function __construct($when, $position = null)
    {
        $this->when = clone $when;
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

        $position = isset($input['position']) ? $input['position'] : null;

        return new self($interval, $position);
    }
}
