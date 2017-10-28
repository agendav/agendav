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

use AgenDAV\Data\Principal;


/**
 * Interface for a principals repository
 */
interface PrincipalsRepository
{
    /**
     * Returns a Principal object for a given URL
     *
     * @param string $url
     * @return \AgenDAV\Data\Principal
     */
    public function get($url);

    /**
     * Searchs a principal using a filter string
     *
     * @param string $filter
     * @return \AgenDAV\Data\Principal[]
     */
    public function search($filter);
}
