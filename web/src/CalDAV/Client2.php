<?php
namespace AgenDAV\CalDAV;

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

use \AgenDAV\XML\Generator;

class Client2
{
    /** @type AgenDAV\Http\Client   HTTP client used */

    protected $http_client;

    /** @type AgenDAV\XML\Generator XML generator */
    protected $xml_generator;

    /** @type AgenDAV\XML\Parser XML parser */
    protected $xml_parser;


    /**
     * @param AgenDAV\Http\Client $http_client
     * @param AgenDAV\XML\Generator $xml_generator
     * @param AgenDAV\XML\Parser $xml_parser
     */
    public function __construct(
        \AgenDAV\Http\Client $http_client,
        \AgenDAV\XML\Generator $xml_generator,
        \AgenDAV\XML\Parser $xml_parser
    )
    {
        $this->http_client = $http_client;
        $this->xml_generator = $xml_generator;
        $this->xml_parser = $xml_parser;
    }

    /**
     * Retrieves DAV:current-user-principal for the current authenticated
     * user
     *
     * @return string   Principal URL
     */
    public function getCurrentUserPrincipal()
    {
        $body = $this->xml_generator->propfindBody([
            '{DAV:}current-user-principal'
        ]);

        $response = $this->propfind('', 0, $body);

        if (count($response) === 0) {
            throw new \UnexpectedValueException('No current-user-principal was returned by the server!');
        }

        reset($response);
        $result = current($response);

        return $result;
    }

    /**
     * Queries the CalDAV server for the calendar-home-set. It has to be
     * requested on the principal URL
     *
     * @param string $principal_url Principal URL
     * @return string   Calendar home set for given principal
     */
    public function getCalendarHomeSet($principal_url)
    {
        $body = $this->xml_generator->propfindBody([
            '{urn:ietf:params:xml:ns:caldav}calendar-home-set'
        ]);

        $response = $this->propfind($principal_url, 0, $body);

        if (count($response) === 0) {
            throw new \UnexpectedValueException('No calendar-home-set was returned by the server!');
        }

        reset($response);
        $result = current($response);

        return $result;
    }
    

    /**
     * Issues a PROPFIND and parses the response
     *
     * @param string $url   URL
     * @param int $depth   Depth header
     * @param string $body  Request body
     * @result array key-value array, where keys are paths and properties are values
     */
    public function propfind($url, $depth, $body)
    {
        $this->http_client->setHeader('Depth', $depth);
        $response = $this->http_client->request('PROPFIND', $url, $body);

        $contents = $response->getBody()->getContents();
        $parsed_response = $this->xml_parser->parseMultistatus($contents);

        // If depth was 0, we only return the top item
        if ($depth === 0) {
            reset($parsed_response);
            $result = current($parsed_response);
            return isset($result[200])?$result[200]:[];
        }

        $result = [];
        foreach($parsed_response as $href => $statusList) {
            $result[$href] = isset($statusList[200])?$statusList[200]:[];
        }

        return $result;
    }
    

}
