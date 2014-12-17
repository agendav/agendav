<?php

namespace AgenDAV;

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

/**
 * This interface models an Event
 *
 */
interface Event
{
    public function isRecurrent();

    public function expand(\DateTime $start, \DateTime $end, $url = null, $etag = null);

    /**
     * Checks if a RECURRENCE-ID string (that could be the result of
     * expanding a recurrent event) was an exception to the rule or not
     *
     * @param string $recurrence_id RECURRENCE-ID value
     * @return boolean
     */
    public function isException($recurrence_id);
}

