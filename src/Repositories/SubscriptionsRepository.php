<?php

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

namespace AgenDAV\Repositories;

use AgenDAV\Data\Subscription;
use AgenDAV\Data\Principal;
use AgenDAV\CalDAV\Resource\Calendar;


/**
 * Interface for a shares repository
 *
 * @author Jorge López Pérez <jorge@adobo.org>
 */
interface SubscriptionsRepository
{
    /**
     * Returns all calendars subscribed a user
     *
     * @param \AgenDAV\Data\Principal $principal  User principal
     * @return \AgenDAV\Data\Subscription[]
     */
    public function getSubscriptionsFor(Principal $principal);

    /**
     * Returns a specific calendar subscribed by a user
     *
     * @param \AgenDAV\Data\Calendar  $calendar  Calendar object
     * @param \AgenDAV\Data\Principal $principal User principal
     * @return \AgenDAV\Data\Subscription[]
     */
    public function getSubscriptionByUrl(Calendar $calendar, Principal $principal);

    /**
     * Stores a subscription in the database
     *
     * @param AgenDAV\Data\Subscription $subscription  Subscription object
     */
    public function save(Subscription $subscription);

    /**
     * Removes a subscription from the database
     *
     * @param AgenDAV\Data\Subscription $subscription  Subscription object
     */
    public function remove(Subscription $subscription);

}
