<?php

namespace AgenDAV\XML;

use Sabre\DAV\XMLUtil;
use Sabre\DAV\Property\ResponseList;
use Sabre\DAV\Exception\BadRequest;

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

/**
 * Helper class to parse CalDAV XML
 *
 */
class Parser
{

    /** @type Array Key-value array containing Property classes from SabreDAV */
    protected $property_map;

    /**
     * @param Array $property_map
     */
    public function __construct(Array $property_map = [])
    {
        $this->property_map = $property_map;;
        $this->propertyMap['{DAV:}resourcetype'] = 'Sabre\\DAV\\Property\\ResourceType';
    }
    

    /**
     * Parses a WebDAV multistatus response body
     * Method taken from SabreDAV client
     *
     * This method returns an array with the following structure
     *
     * [
     *   'url/to/resource' => [
     *     '200' => [
     *        '{DAV:}property1' => 'value1',
     *        '{DAV:}property2' => 'value2',
     *     ],
     *     '404' => [
     *        '{DAV:}property1' => null,
     *        '{DAV:}property2' => null,
     *     ],
     *   ],
     *   'url/to/resource2' => [
     *      .. etc ..
     *   ]
     * ]
     *
     *
     * @param string $body xml body
     * @return array
     */
    public function parseMultiStatus($body) {

        try {
            $dom = XMLUtil::loadDOMDocument($body);
        } catch (BadRequest $e) {
            throw new \InvalidArgumentException('The body passed to parseMultiStatus could not be parsed');
        }

        $responses = ResponseList::unserialize(
            $dom->documentElement,
            $this->property_map
        );

        $result = [];

        foreach($responses->getResponses() as $response) {
            $result[$response->getHref()] = $response->getResponseProperties();
        }

        return $result;
    }


}

