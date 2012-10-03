<?php
namespace AgenDAV\CalDAV;

/*
 * Copyright 2012 Jorge López Pérez <jorge@adobo.org>
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

class URLGenerator implements IURLGenerator
{

    /**
     * CalDAV base URL 
     *
     * @var string
     * @access private
     */
    private $base;

    /**
     * Principal URL template
     *
     * @var string
     * @access private
     */
    private $principal_template;

    /**
     * calendar-home-set URL template
     *
     * @var string
     * @access private
     */
    private $calendar_homeset_template;

    /**
     * CalDAV public URL
     *
     * @var string
     * @access private
     */
    private $public_caldav_url;

    /**
     * Creates a new URL generator
     *
     * @param string $base Base CalDAV URL
     * @param string $principal_template Principal URL template
     * @param string $calendar_homeset_template Calendar home set template
     * @param string $public_caldav_url Base CalDAV public URL
     * @access public
     * @return void
     */
    public function __construct($base, $principal_template, $calendar_homeset_template, $public_caldav_url) {
        $this->base = $base;
        $this->principal_template = $principal_template;
        $this->calendar_homeset_template = $calendar_homeset_template;
        $this->public_caldav_url = $calendar_homeset_template;
    }

    /**
     * Returns base URL
     *
     * @return string Base URL
     */
    public function getBaseURL()
    {
        return $this->base;
    }

    /**
     * Builds a principal URL
     *
     * @param string $username User name
     * @param bool $absolute Use absolute URL or relative
     *
     * @return string Principal URL
     */
    public function generatePrincipal($username, $absolute = false)
    {
        $url = preg_replace(
            '/%u/',
            $username,
            $this->principal_template
        );

        return $absolute ? $url : $this->getPath($url);
    }


    /**
     * Builds the calendar-home-set URL
     *
     * @param string $username User name
     * @param bool $absolute Use absolute URL or relative
     *
     * @return string Calendar home set URL
     */
    public function generateCalendarHomeSet($username, $absolute = false)
    {
        $url = preg_replace(
            '/%u/',
            $username,
            $this->calendar_homeset_template
        );

        return $absolute ? $url : $this->getPath($url);
    }

    /**
     * Extracts path from a provided URL 
     *
     * @param string $url URL
     * @return string Path from the URL
     */
    private function getPath($url)
    {
        $parsed = parse_url($url);

        return $parsed['path'];
    }

    /**
     * Builds a public URL for a given resource
     *
     * @param string $path Path to the resource
     *
     * @return string Public URL
     */
    public function generatePublicURL($path)
    {
        return $this->public_caldav_url . $path;
    }

}
