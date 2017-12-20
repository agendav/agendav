<?php

namespace AgenDAV\CalDAV\Filter;

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

use AgenDAV\CalDAV\ComponentFilter;
use Sabre\Xml\Writer;

/**
 * Filter for principal property search 
 * (https://tools.ietf.org/html/rfc3744#section-9.4)
 */
class PrincipalPropertySearch implements ComponentFilter
{
    /** @property string $input */
    protected $input;

    /**
     * @param string $input User input
     */
    public function __construct($input)
    {
        $this->input = $input;
    }

    /**
     * Adds a filter to the passed Sabre\Xml\Writer object
     *
     * @param \Sabre\Xml\Writer $writer XML writer
     * @return void
     */
    public function addFilter(\Sabre\Xml\Writer $writer)
    {
        $writer->startElement('{DAV:}principal-property-search');
        $writer->writeAttribute('test', 'anyof');

        foreach (['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set', '{DAV:}displayname'] as $property) {
            $writer->write([
                '{DAV:}property-search' => [
                    '{DAV:}prop' => [
                        $property => []
                    ],
                    '{DAV:}match' => $this->input,
                ]
            ]);
        }

        $writer->write([
            '{DAV:}prop' => [
                '{DAV:}displayname' => [],
                // TODO make this property configurable
                '{DAV:}email' => [],
            ]
        ]);

        $writer->endElement();
    }
}

