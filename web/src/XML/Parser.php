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
        $this->property_map['{DAV:}resourcetype'] = 'Sabre\\DAV\\Property\\ResourceType';
    }

    /**
     * Parses a multistatus response.
     *
     * By default, returns an associative array containing all the OK properties for
     * each resource found:
     *
     * [ '/url/resource1' => [
     *                          '{DAV:}displayname' => '...',
     *                           ...
     *                       ],
     *   '/url/resource2' => [
     *                          '....' => '...',
     *                          ...
     *                       ],
     * ]
     *
     * If $single_element is set to true, then just the first element is returned.
     * This is useful when Depth: 0 was used on the original request
     *
     * @param string $body XML multistatus body
     * @param boolean $single_element If set to true, only the first resource
     *                                properties will be returned
     * @return array
     */
    public function extractPropertiesFromMultistatus($body, $single_element = false)
    {
        $parsed_xml = $this->parseMultistatus($body);
        return $this->getOkPropertiesFromMultistatus($parsed_xml, $single_element);
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
    protected function parseMultistatus($body)
    {
        try {
            $dom = XMLUtil::loadDOMDocument($body);
        } catch (BadRequest $e) {
            throw new \InvalidArgumentException('The body passed to parsePropertiesFromMultistatus could not be parsed: ' . $e->getMessage());
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

    /**
     * Formats a parsed multistatus response (from parseMultiStatus) to
     * get it as an associative array of path => [properties that were found]
     *
     * @param array $parsed_multistatus From parseMultiStatus
     * @param boolean $single_element Keep only first element (Depth: 0) or not
     * @return array Associative array, url => [properties]. Properties are in
     *               Clark notation
     */
    protected function getOkPropertiesFromMultistatus(array $parsed_multistatus, $single_element = false)
    {
        // If depth was 0, we only return the top item
        if ($single_element === true) {
            reset($parsed_multistatus);
            $result = current($parsed_multistatus);
            return isset($result[200])?$result[200]:[];
        }

        $result = [];
        foreach($parsed_multistatus as $href => $statusList) {
            $result[$href] = isset($statusList[200])?$statusList[200]:[];
        }

        return $result;
    }
}

