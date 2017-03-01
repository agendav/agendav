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
 * Test filter that just adds a fake tag
 */
class Test implements ComponentFilter
{

    /** @var string */
    private $tag;

    /**
     * @param string $tag In Clark notation
     */
    public function __construct($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Adds a filter to the passed Sabre\Xml\Writer object
     *
     * @param \Sabre\Xml\Writer $writer XML writer
     * @return void
     */
    public function addFilter(\Sabre\Xml\Writer $writer)
    {
        $writer->write([ $this->tag => [] ]);
    }
}
