<?php

namespace AgenDAV\CalDAV\Filter;

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
     * Returns a DOMElement cotaining this filter
     *
     * @param \DOMDocument $document Initial DOMDocument, required to
     *                               generate a valid \DOMElement
     * @result \DOMElement $element
     */
    public function generateFilterXML(\DOMDocument $document)
    {
        $time_range = $document->createElement('C:time-range');
        $time_range->setAttribute('start', $this->start);
        $time_range->setAttribute('end', $this->end);

        return $time_range;
    }
}

