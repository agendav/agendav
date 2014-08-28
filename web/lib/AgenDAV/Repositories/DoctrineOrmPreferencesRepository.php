<?php

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

namespace AgenDAV\Repositories;

use Doctrine\ORM\EntityManager;
use AgenDAV\Data\Preferences;


class DoctrineOrmPreferencesRepository implements PreferencesRepository
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
     * Gets all preferences for the given user
     *
     * @param string $username  User name
     * @return AgenDAV\Data\Preferences
     */
    public function userPreferences($username)
    {
        $preferences = $this->em->find('AgenDAV\Data\Preferences', $username);

        return $preferences === null ? new Preferences() : $preferences;
    }

    /**
     * Saves user preferences
     *
     * @param string $username User name
     * @param \AgenDAV\Data\Preferences $preferences
     */
    public function save($username, \AgenDAV\Data\Preferences $preferences)
    {
        $preferences->setUsername($username);
        $this->em->persist($preferences);
        $this->em->flush();
    }
}
