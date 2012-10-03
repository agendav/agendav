<?php 
namespace AgenDAV\CalendarChannels;

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

class CalendarHomeSet implements IChannel
{
    /**
     * CalDAV client 
     * 
     * @var \AgenDAV\CalDAV\ICalDAVClient
     * @access private
     */
    private $client;

    /**
     * Instantiates a new CalendarHomeSet channel
     * 
     * @param \AgenDAV\CalDAV\ICalDAVClient $client 
     * @access public
     * @return void
     */
    public function __construct(\AgenDAV\CalDAV\ICalDAVClient $client)
    {
        $this->client = $client;
    }

    /**
     * Configure this channel 
     * 
     * @param Array $options Options for this channel
     * @access public
     * @return void
     */
    public function configure($options)
    {
    }

    /**
     * Get name for current channel 
     * 
     * @access public
     * @return string
     */
    public function getName()
    {
        return 'CalendarHomeSet';
    }

    /**
     * Gets current user calendars 
     * 
     * @access public
     * @return Array [path => \AgenDAV\Data\CalendarInfo]
     */
    public function getCalendars()
    {
        return $this->client->getCalendars();
    }
}
