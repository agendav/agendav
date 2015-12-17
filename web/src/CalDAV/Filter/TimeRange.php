<?php

namespace AgenDAV\CalDAV\Filter;

/*
 * Copyright 2014-2015 Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\CalDAV\ComponentFilter;

/**
 * <time-range> filter for REPORTs
 */
class TimeRange implements ComponentFilter
{
    protected $start;

    protected $end;

    /**
     * @param string $start ISO8601 based time string, in UTC
     * @param string $end ISO8601 based time string, in UTC
     */
    public function __construct($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Adds a filter to the passed Sabre\Xml\Writer object
     *
     * @param \Sabre\Xml\Writer $writer XML writer
     * @return void
     */
    public function addFilter(\Sabre\Xml\Writer $writer)
    {
        $writer->write([
            [
                'name' => '{urn:ietf:params:xml:ns:caldav}time-range',
                'value' => null,
                'attributes' => [
                    'start' => $this->start,
                    'end' => $this->end,
                ],
            ]
        ]);
    }
}

