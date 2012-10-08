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

/**
 * ICalDAVClient 
 *
 * Interface for CalDAV clients
 */
interface ICalDAVClient
{
    /**
     * Checks current user authentication against current user principal URL
     * 
     * @access public
     * @return boolean
     */
    public function checkAuthentication();

    /**
     * Checks if provided resource is accesible for current user 
     * 
     * @param string $href 
     * @access public
     * @return boolean
     */
    public function isAccessible($href);

    /**
     * Retrieves calendars from the given list 
     * 
     * @param Array $list List of hrefs
     * @access public
     * @return Array
     */
    public function getCalendars($list = null);

    /**
     * Gets current events from given resource
     * 
     * @param string $href Resource URL/path
     * @param int $start Starting timestamp
     * @param int $end End timestamp
     * @access public
     * @return Array
     */
    public function fetchEvents($href, $start, $end);

    /**
     * Finds an entry using its UID 
     * 
     * @param string $href URL/path
     * @param string $uid Element UID
     * @access public
     * @return Array
     */
    public function fetchEntryByUID($href, $uid);

    /**
     * Puts a resource on the server 
     * 
     * @param string $href URL/path
     * @param string $data Text data to be put (iCalendar)
     * @param string $etag Last known Etag for this resource
     * @access public
     * @return string New Etag on success, HTTP result code otherwise
     */
    public function putResource($href, $data, $etag = null);

    /**
     * Deletes a resource on the server 
     * 
     * @param string $href URL/path
     * @param string $etag Last known Etag for this resource
     * @access public
     * @return mixed true on success, HTTP result code otherwise
     */
    public function deleteResource($href, $etag = null);

    /**
     * Changes properties for a given resource using PROPPATCH
     *
     * @param \AgenDAV\Data\CalendarInfo $calendar Calendar resource to be modified
     * @return mixed  true on successful creation, HTTP result code otherwise
     */
    public function changeResource(\AgenDAV\Data\CalendarInfo $calendar);

    /**
     * Creates a new calendar inside a principal collection
     *
     * @param \AgenDAV\Data\CalendarInfo $calendar New calendar to be added
     * @return mixed true on successful creation, HTTP result code otherwise
     */
    public function createCalendar(\AgenDAV\Data\CalendarInfo $calendar);

    /**
     * Sets an ACL on a resource
     *
     * @param string $href Relative URL to the resource
     * @param Array $share_with List of principals+permissions we want to share this calendar with
     * @return mixed true on success, HTTP code otherwise
     */
    public function setACL($href, $acls);

    /**
     * Searchs a principal based on passed conditions.
     *
     * @param string $dn Display name
     * @param string $user_address Mail address
     * @param bool $use_or If set to true, combines previous conditions using an 'or' operand
     * @return Array Principals found for the given conditions
     */
    public function principalSearch($dn, $user_address, $use_or);
}
