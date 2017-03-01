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
 * <uid> filter for REPORTs
 */
class Uid implements ComponentFilter
{
    /** @property string Uid */
    protected $uid;

    /**
     * @param string $uid Calendar object uid
     */
    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    /**
     * Adds a filter to the passed Sabre\Xml\Writer object
     *
     * @param \Sabre\Xml\Writer $writer XML writer
     * @return void
     */
    public function addFilter(\Sabre\Xml\Writer $writer)
    {
        $ns_c = '{urn:ietf:params:xml:ns:caldav}';

        $writer->startElement($ns_c . 'prop-filter');
        $writer->writeAttribute('name', 'UID');

        $writer->startElement($ns_c . 'text-match');
        $writer->writeAttribute('collation', 'i;octet');
        $writer->write($this->uid);
        $writer->endElement();

        $writer->endElement();
    }
}

