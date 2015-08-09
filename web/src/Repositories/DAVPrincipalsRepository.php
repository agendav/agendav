<?php

/*
 * Copyright 2015 Jorge López Pérez <jorge@adobo.org>
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
use AgenDAV\XML\Toolkit;
use AgenDAV\CalDAV\Client;


/**
 * Principals repository class that just reads principals from a CalDAV server
 */
class DAVPrincipalsRepository implements PrincipalsRepository
{

    /** @type AgenDAV\XML\Toolkit */
    protected $xml_toolkit;

    /** @type AgenDAV\CalDAV\Client */
    protected $caldav_client;

    /**
     * Builds a new repository
     *
     * @param AgenDAV\XML\Toolkit $xml_toolkit
     * @param AgenDAV\CalDAV\Client $caldav_client
     */
    public function __construct($xml_toolkit, $caldav_client)
    {
        $this->xml_toolkit = $xml_toolkit;
        $this->caldav_client = $caldav_client;
    }

    /**
     * Returns a Principal object for a given URL
     *
     * @param string $url
     * @return AgenDAV\Data\Principal
     */
    public function get($url)
    {
        $body = $this->xml_toolkit->generateRequestBody(
            'PROPFIND',
            [ '{DAV:}displayname' ]
        );

        $properties = $this->caldav_client->propfind($url, 0, $body);

        $result = new Principal($url);

        if (isset($properties[Principal::DISPLAYNAME])) {
            $result->setDisplayName($properties[Principal::DISPLAYNAME]);
        }

        // TODO read and store email
        return $result;
    }
}
