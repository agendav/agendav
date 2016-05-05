<?php

namespace AgenDAV\Data\Transformer;

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

use League\Fractal;
use AgenDAV\CalDAV\Resource\Calendar;


class CalendarTransformer extends Fractal\TransformerAbstract
{

    /** @var string */
    protected $principal_url;

    /**
     * Creates a new Calendar transformer
     *
     * @param string $user_principal Current user principal
     */
    public function __construct($principal_url)
    {
        $this->principal_url = $principal_url;
    }

    /**
     * Transforms a Calendar
     *
     * @param AgenDAV\CalDAV\Resource\Calendar $calendar
     */
    public function transform(Calendar $calendar)
    {
        $owner_url = $calendar->getOwner()->getUrl();

        $result = [
            'url' => $calendar->getUrl(),
            'calendar' => $calendar->getUrl(),
            'displayname' => $calendar->getProperty(Calendar::DISPLAYNAME),
            'color' => $calendar->getProperty(Calendar::COLOR),
            'order' => (int) $calendar->getProperty(Calendar::ORDER),
            'ctag' => $calendar->getProperty(Calendar::CTAG),
            'is_owned' => ($owner_url === $this->principal_url),
            'is_shared' => ($owner_url !== $this->principal_url),
            'owner' => $owner_url,
            'writable' => $calendar->isWritable(),
            'shares' => [],
        ];

        $shares = $calendar->getShares();
        foreach ($shares as $share) {
            $result['shares'][] = [
                'sid' => $share->getSid(),
                'with' => $share->getWith(),
                'displayname' => $share->getWith(),
                'rw' => (int)$share->isWritable(),
            ];
        }

        return $result;
    }
}
