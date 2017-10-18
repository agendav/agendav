<?php
namespace AgenDAV\Sharing;

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

use AgenDAV\Repositories\SharesRepository;
use AgenDAV\Repositories\PrincipalsRepository;
use AgenDAV\Data\Principal;
use AgenDAV\Data\Share;
use AgenDAV\CalDAV\Resource\Calendar;

/**
 * This is a service to retrieve shares and related principals. Also proxies a SharesRepository 
 */
class SharingResolver implements SharesRepository
{
    /** @var \AgenDAV\Repositories\SharesRepository */
    protected $shares_repository;

    /** @var \AgenDAV\Repositories\PrincipalsRepository */
    protected $principals_repository;

    /**
     * @param Symfony\Component\HttpFoundation\Session\Session $session
     * @param \AgenDAV\CalDAV\Client $client
     * @param AgenDAV\Repositories\SharesRepository $shares_repository
     * @param AgenDAV\Repositories\PrincipalsRepository $principals_repository
     */
    public function __construct(
        SharesRepository $shares_repository,
        PrincipalsRepository $principals_repository
    )
    {
        $this->shares_repository = $shares_repository;
        $this->principals_repository = $principals_repository;
    }

    /**
     * Resolves principals for a list of shares
     *
     * @param \AgenDAV\Data\Share[] $shares
     * @return void
     */
    public function resolveShares(array $shares)
    {
        foreach ($shares as $share) {
            $share_with = $share->getWith();
            $principal = $this->principals_repository->get($share_with);
            $share->setPrincipal($principal);
        }
    }

    /**
     * Returns all calendars shared with a user
     *
     * @param \AgenDAV\Data\Principal $principal  User principal
     * @return \AgenDAV\Data\Share[]
     */
    public function getSharesFor(Principal $principal)
    {
        $shares = $this->shares_repository->getSharesFor($principal);
        $this->resolveShares($shares);

        return $shares;
    }

    /**
     * Returns all grants that have been given to a calendar
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar
     * @return \AgenDAV\Data\Share[]
     */
    public function getSharesOnCalendar(Calendar $calendar)
    {
        $shares = $this->shares_repository->getSharesOnCalendar($calendar);
        $this->resolveShares($shares);

        return $shares;
    }

    /**
     * Stores a grant on the database
     *
     * @param AgenDAV\Data\Share $share  Share object
     */
    public function save(Share $share)
    {
        $this->shares_repository->save($share);
    }

    /**
     * Removes a grant for a calendar
     *
     * @param AgenDAV\Data\Share $share  Share object
     */
    public function remove(Share $share)
    {
        $this->shares_repository->remove($share);
    }

    /**
     * Saves all calendar shares. Any other existing shares will get removed
     *
     * @param AgenDAV\CalDAV\Resource\Calendar $calendarj
     */
    public function saveFromCalendar(Calendar $calendar)
    {
        $this->shares_repository->saveFromCalendar($calendar);
    }

    /**
     * Retrieves the Share object for a calendar which is shared with
     * a given principal
     *
     * @param AgenDAV\CalDAV\Resource\Calendar $calendar
     * @param \AgenDAV\Data\Principal $principal  User principal
     */
    public function getSourceShare(Calendar $calendar, Principal $principal)
    {
        $share = $this->shares_repository->getSourceShare($calendar, $principal);
        $this->resolveShares([ $share ]);

        return $share;
    }
}
