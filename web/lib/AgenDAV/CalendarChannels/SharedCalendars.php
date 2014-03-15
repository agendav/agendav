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

class SharedCalendars implements IChannel
{
    /**
     * CalDAV client 
     * 
     * @var \AgenDAV\CalDAV\ICalDAVClient
     * @access private
     */
    private $client;

    /**
     * CodeIgniter shared calendars model
     * 
     * @var Object
     * @access private
     */
    private $model;

    /**
     * Current username
     * 
     * @var string
     * @access private
     */
    private $username;

    /**
     * Instantiates a new SharedCalendars channel
     * 
     * @param \AgenDAV\CalDAV\ICalDAVClient $client 
     * @param Object $model shared_calendars model instance from CodeIgniter
     * @access public
     * @return void
     */
    public function __construct(\AgenDAV\CalDAV\ICalDAVClient $client, $model)
    {
        $this->client = $client;
        $this->model = $model;
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
        $this->username = $options['username'];
    }

    /**
     * Get name for current channel 
     * 
     * @access public
     * @return string
     */
    public function getName()
    {
        return 'SharedCalendars';
    }

    /**
     * Gets calendars which current user has been granted access to
     *
     * After retrieving them from the database, calendars are fetched from CalDAV server too
     * 
     * @access public
     * @return Array [path => \AgenDAV\Data\Calendar]
     */
    public function getCalendars()
    {
        $shared_calendars = array();
        $shared_db = $this->model->givenAccesses($this->username);

        if (count($shared_db) > 0) {
            $shared_calendars = $this->client->getCalendars(array_keys($shared_db));
            $shared_calendars = $this->model->setProperties($shared_calendars, $shared_db);
        }

        return $shared_calendars;
    }
}
