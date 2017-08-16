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

use Doctrine\ORM\EntityManager;
use AgenDAV\Data\Subscription;
use AgenDAV\Data\Principal;
use AgenDAV\CalDAV\Resource\Calendar;


/**
 * Implements the subscriptions repository using Doctrine ORM
 *
 * @author Jorge López Pérez <jorge@adobo.org>
 */
class DoctrineOrmSubscriptionsRepository implements SubscriptionsRepository
{
    /**
     * @var EntityManager
     */
    private $em;


    /**
     * @param Doctrine\ORM\EntityManager Entity manager
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns all calendars subscribed by a user
     *
     * @param \AgenDAV\Data\Principal $principal  User principal
     * @return \AgenDAV\Data\Subscription[]
     */
    public function getSubscriptionsFor(Principal $principal)
    {
        $subscriptions = $this->em->getRepository('AgenDAV\Data\Subscription')
            ->findBy(['owner' => $principal->getUrl()]);

        return $subscriptions;
    }

    /**
     * Returns a specific calendar subscribed by a user
     *
     * @param \AgenDAV\Data\Calendar  $calendar  Calendar object
     * @param \AgenDAV\Data\Principal $principal User principal
     * @return \AgenDAV\Data\Subscription[]
     */
    public function getSubscriptionByUrl(Calendar $calendar, Principal $principal)
    {
        $subscriptions = $this->em->getRepository('AgenDAV\Data\Subscription')
            ->findOneBy(['owner' => $principal->getUrl(), 'calendar'=>$calendar->getUrl()]);

        return $subscriptions;
    }

    /**
     * Stores a subscription in the database
     *
     * @param AgenDAV\Data\Subscription $subscription  Subscription object
     */
    public function save(Subscription $subscription)
    {
        $this->em->persist($subscription);
        $this->em->flush();
    }

    /**
     * Removes a subscription from the database
     *
     * @param AgenDAV\Data\Subscription $subscription  Subscription object
     */
    public function remove(Subscription $subscription)
    {
        $this->em->remove($subscription);
        $this->em->flush();
    }

}
