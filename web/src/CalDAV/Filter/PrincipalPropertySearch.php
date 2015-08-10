<?php

namespace AgenDAV\CalDAV\Filter;

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

use AgenDAV\CalDAV\ComponentFilter;

/**
 * Filter for principal property search 
 * (https://tools.ietf.org/html/rfc3744#section-9.4)
 */
class PrincipalPropertySearch implements ComponentFilter
{
    /** @property string input */
    protected $input;

    /**
     * @param string $input User input
     */
    public function __construct($input)
    {
        $this->input = $input;
    }

    /**
     * Returns a DOMElement cotaining this filter
     *
     * @param \DOMDocument $document Initial DOMDocument, required to
     *                               generate a valid \DOMElement
     * @result \DOMElement $element
     */
    public function generateFilterXML(\DOMDocument $document)
    {
        $principal_property_search = $document->createElement('d:principal-property-search');
        $principal_property_search->setAttribute('test', 'anyof');

        foreach (['C:calendar-user-address-set', 'd:displayname'] as $property) {
            $property_search = $document->createElement('d:property-search');
            $prop = $document->createElement('d:prop');
            $current_property = $document->createElement($property);
            $prop->appendChild($current_property);

            $match = $document->createElement('d:match', $this->input);
            $property_search->appendChild($prop);
            $property_search->appendChild($match);
            $principal_property_search->appendChild($property_search);
        }

        $return_prop = $document->createElement('d:prop');
        $displayname = $document->createElement('d:displayname');
        $email = $document->createElement('d:email');
        $return_prop->appendChild($displayname);
        $return_prop->appendChild($email);
        $principal_property_search->appendChild($return_prop);

        return $principal_property_search;
    }
}

