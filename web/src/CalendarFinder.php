<?php
namespace AgenDAV;

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

use AgenDAV\Session\Session;
use AgenDAV\Repositories\SharesRepository;
use AgenDAV\CalDAV\Client;
use AgenDAV\CalDAV\Resource\Calendar;

/**
 * This class is used to find all accessible calendars for an user
 */
class CalendarFinder
{
    /** @var boolean */
    protected $sharing_enabled;

    /** @var \AgenDAV\CalDAV\Client */
    protected $client;

    /** @var \AgenDAV\Repositories\SharesRepository */
    protected $shares_repository;

    /** @var \AgenDAV\Session\Session */
    protected $session;

    /**
     * @param \AgenDAV\Session\Session $session
     * @param \AgenDAV\CalDAV\Client $client
     */
    public function __construct(Session $session, Client $client)
    {
        $this->sharing_enabled = false;
        $this->client = $client;
        $this->session = $session;
    }

    /**
     * Sets the shares repository for this finder. Until it is called,
     * the finder disables all functionalities related to shared calendars
     *
     * @param \AgenDAV\Repositories\SharesRepository $shares_repository
     */
    public function setSharesRepository(SharesRepository $shares_repository)
    {
        $this->sharing_enabled = true;
        $this->shares_repository = $shares_repository;
    }

    /**
     * Returns all calendars for the current user
     *
     * @return \AgenDAV\CalDAV\Resource\Calendar[] Array of calendars
     */
    public function getCalendars()
    {
        $calendar_home_set = $this->session->get('calendar_home_set');

        $result = $this->client->getCalendars($calendar_home_set);

        if ($this->sharing_enabled) {
            $username = $this->session->get('username');
            // Add share info to own calendars
            $this->addSharedInfoAsOwner($result);

            // And load calendars shared with current user
            $shared_calendars = $this->getSharedCalendars($username);

            $result = array_merge($result, $shared_calendars);
        }

        return $result;
    }

    /**
     * Gets shared calendars
     *
     * @return \AgenDAV\CalDAV\Resource\Calendar[]
     */
    protected function getSharedCalendars($username)
    {
        $result = [];

        $shares = $this->shares_repository->getSharesFor($username);
        foreach ($shares as $share) {
            $calendar_url = $share->getPath();
            try {
                $calendar = $this->client->getCalendarByUrl($calendar_url);
            } catch (\Exception $e) {
                // ACL was probably removed or modified. Ignore this calendar
                continue;
            }

            $calendar->setShared(true);
            $calendar->setOwner($share->getGrantor());

            $custom_properties = $share->getProperties();
            $this->applySharedProperties($calendar, $custom_properties);

            $result[] = $calendar;
        }

        return $result;
    }

    /**
     * Applies custom properties to a calendar
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar
     * @param Array $properties
     * @return void
     */
    protected function applySharedProperties(Calendar $calendar, array $properties)
    {
        // These are not real properties (with their XML namespace)
        foreach ($properties as $property => $value) {
            switch ($property) {
                case 'displayname':
                    $calendar->setProperty(Calendar::DISPLAYNAME, $value);
                    break;
                case 'color':
                    $calendar->setProperty(Calendar::COLOR, $value);
                    break;
                default:
                    // Ignore it
            }
        }
    }

    /**
     * undocumented function
     *
     * @return void
     */
    protected function addSharedInfoAsOwner(Array $calendars)
    {
        foreach ($calendars as $calendar) {
            $calendar_url = $calendar->getUrl();
            $shares = $this->shares_repository->getSharesOnCalendar($calendar_url);
            $calendar->setShares($shares);
        }
    }
}
