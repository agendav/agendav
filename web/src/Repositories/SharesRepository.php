<?php

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

namespace AgenDAV\Repositories;

use AgenDAV\Data\Share;
use AgenDAV\Data\Principal;
use AgenDAV\CalDAV\Resource\Calendar;


/**
 * Interface for a shares repository
 *
 * @author Jorge López Pérez <jorge@adobo.org>
 */
interface SharesRepository
{
    /**
     * Returns all calendars shared with a user
     *
     * @param \AgenDAV\Data\Principal $principal  User principal
     * @return \AgenDAV\Data\Share[]
     */
    public function getSharesFor(Principal $principal);

    /**
     * Returns all grants that have been given to a calendar
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar
     * @return \AgenDAV\Data\Share[]
     */
    public function getSharesOnCalendar(Calendar $calendar);

    /**
     * Stores a grant on the database
     *
     * @param AgenDAV\Data\Share $share  Share object
     */
    public function save(Share $share);

    /**
     * Removes a grant for a calendar
     *
     * @param AgenDAV\Data\Share $share  Share object
     */
    public function remove(Share $share);

    /**
     * Saves all calendar shares. Any other existing shares will get removed
     *
     * @param AgenDAV\CalDAV\Resource\Calendar $calendarj
     */
    public function saveFromCalendar(Calendar $calendar);

    /**
     * Retrieves the Share object for a calendar which is shared with
     * a given principal
     *
     * @param AgenDAV\CalDAV\Resource\Calendar $calendar
     * @param \AgenDAV\Data\Principal $principal  User principal
     */
    public function getSourceShare(Calendar $calendar, Principal $principal);
}
