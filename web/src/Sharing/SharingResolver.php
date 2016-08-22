<?php
namespace AgenDAV\Sharing;

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

use AgenDAV\Repositories\SharesRepository;
use AgenDAV\Repositories\PrincipalsRepository;
use AgenDAV\Data\Principal;
use AgenDAV\Data\Share;

/**
 * This is a service to retrieve shares and related principals
 */
class SharingResolver
{
    /** @var AgenDAV\Repositories\SharesRepository */
    protected $shares_repository;

    /** @var AgenDAV\Repositories\PrincipalsRepository */
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
     * @param AgenDAV\Data\Share[] $shares
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
}
