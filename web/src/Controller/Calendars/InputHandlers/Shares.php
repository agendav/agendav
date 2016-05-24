<?php

namespace AgenDAV\Controller\Calendars\InputHandlers;

/*
 * Copyright 2016 Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\Data\Share;

class Shares
{
    /**
     * Generates an array of Share objects using the provided input
     *
     * @param string[] $with    Array of grantees
     * @param string[] $rw      Array of read/write permissions (0 or 1)
     * @param string $owner      Calendar owner principal URL
     * @param string $calendar      Calendar URL
     * @return AgenDAV\Data\Share[] in the same order as input
     * @throws \LengthException If $with and $rw do not have the same number of elements
     */
    public static function buildFromInput(Array $with, Array $rw, $owner, $calendar)
    {
        $result = [];

        $entries = count($with);

        if (count($rw) != $entries) {
            throw new \LengthException('with length <> rw length');
        }

        for ($i=0;$i<$entries;$i++) {
            $writable = ($rw[$i] === '1');

            $share = new Share;
            $share->setWith($with[$i]);
            $share->setWritePermission($writable);
            $share->setOwner($owner);
            $share->setCalendar($calendar);

            $result[$i] = $share;
        }

        return $result;
    }
}
