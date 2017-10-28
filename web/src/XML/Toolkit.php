<?php

namespace AgenDAV\XML;

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

/**
 * XML toolkit class that contains both an XML parser and an XML generator
 */
class Toolkit
{

    /** @param AgenDAV\XML\Parser */
    protected $parser;

    /** @param AgenDAV\XML\Generator */
    protected $generator;

    /**
     * @param \AgenDAV\XML\Parser $parser
     * @param \AgenDAV\XML\Generator $generator
     */
    public function __construct(Parser $parser, Generator $generator)
    {
        $this->parser = $parser;
        $this->generator = $generator;
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
     * @param boolean $first_element If set to true, only the first resource
     *                                properties will be returned
     * @return array
     */
    public function parseMultistatus($body, $first_element = false)
    {
        return $this->parser->extractPropertiesFromMultistatus($body, $first_element);
    }

    /**
     * Generates the XML body for a request
     *
     * @param string $request   One of MKCALENDAR, PROPFIND, PROPPATCH, REPORT-CALENDAR or
     *                          REPORT-PRINCIPAL-SEARCH
     * @param mixed $parameters array of properties, \AgenDAV\CalDAV\ComponentFilter
     *                          object if $request is REPORT or \AgenDAV\CalDAV\Share\ACL
     *                          if $request is ACL
     * @return string XML body
     * @throws \InvalidArgumentException if $request is not recognized
     */
    public function generateRequestBody($request, $parameters)
    {
        switch ($request) {
            case 'MKCALENDAR':
                return $this->generator->mkCalendarBody($parameters);
            case 'PROPFIND':
                return $this->generator->propfindBody($parameters);
            case 'PROPPATCH':
                return $this->generator->proppatchBody($parameters);
            case 'REPORT-CALENDAR':
                return $this->generator->calendarQueryBody($parameters);
            case 'REPORT-PRINCIPAL-SEARCH':
                return $this->generator->principalPropertySearchBody($parameters);
            case 'ACL':
                return $this->generator->aclBody($parameters);
            default:
                throw new \InvalidArgumentException(
                    'Unrecognized REQUEST type ' . $request . '. Could not generate body'
                );
        }
    }
}

