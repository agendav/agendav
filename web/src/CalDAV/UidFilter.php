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

/**
 * <uid> filter for REPORTs
 */
class UidFilter implements ComponentFilter
{
    /** @property string Uid */
    protected $uid;

    /**
     * @param string $uid Event uid
     */
    public function __construct($uid)
    {
        $this->uid = $uid;
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
        $uid_match = $document->createElement('C:prop-filter');
        $uid_match->setAttribute('name', 'UID');

        $text_match = $document->createElement('C:text-match', $this->uid);
        $text_match->setAttribute('collation', 'i;octet');

        $uid_match->appendChild($text_match);
        return $uid_match;
    }
}

