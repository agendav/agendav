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
use AgenDAV\XML\Toolkit;
use AgenDAV\CalDAV\Client;
use AgenDAV\CalDAV\Filter\PrincipalPropertySearch;

/**
 * Principals repository class that just reads principals from a CalDAV server
 */
class DAVPrincipalsRepository implements PrincipalsRepository
{

    /** @type \AgenDAV\XML\Toolkit */
    protected $xml_toolkit;

    /** @type \AgenDAV\CalDAV\Client */
    protected $caldav_client;

    /** @type string */
    protected $email_attribute;

    /**
     * Builds a new repository
     *
     * @param \AgenDAV\XML\Toolkit $xml_toolkit
     * @param \AgenDAV\CalDAV\Client $caldav_client
     * @param string $email_attribute
     */
    public function __construct(Toolkit $xml_toolkit, Client $caldav_client, $email_attribute)
    {
        $this->xml_toolkit = $xml_toolkit;
        $this->caldav_client = $caldav_client;
        $this->email_attribute = $email_attribute;
    }

    /**
     * Returns a Principal object for a given URL
     *
     * @param string $url
     * @return \AgenDAV\Data\Principal
     */
    public function get($url)
    {
        $body = $this->xml_toolkit->generateRequestBody(
            'PROPFIND',
            [ '{DAV:}displayname', '{DAV:}email' ]
        );

        $properties = $this->caldav_client->propfind($url, 0, $body);

        $result = new Principal($url);

        if (isset($properties[Principal::DISPLAYNAME])) {
            $result->setDisplayName($properties[Principal::DISPLAYNAME]);
        }

        if (isset($properties[$this->email_attribute])) {
            $result->setEmail($properties[$this->email_attribute]);
        }

        return $result;
    }

    /**
     * Searchs a principal using a filter string
     *
     * @param string $filter
     * @return \AgenDAV\Data\Principal[]
     */
    public function search($filter)
    {
        $result = [];

        $principal_property_search_filter = new PrincipalPropertySearch($filter);

        $body = $this->xml_toolkit->generateRequestBody(
            'REPORT-PRINCIPAL-SEARCH',
            $principal_property_search_filter
        );

        $response = $this->caldav_client->report('', $body, 0);

        foreach ($response as $url => $properties) {
            $principal = new Principal($url);
            if (isset($properties[Principal::DISPLAYNAME])) {
                $principal->setDisplayName($properties[Principal::DISPLAYNAME]);
            }

            if (isset($properties[$this->email_attribute])) {
                $principal->setEmail($properties[$this->email_attribute]);
            }

            $result[$url] = $principal;
        }

        return $result;
    }
}
