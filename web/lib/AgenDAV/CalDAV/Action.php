<?php

namespace AgenDAV\CalDAV;

/*
 * Copyright 2011-2012 Jorge López Pérez <jorge@adobo.org>
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

class Action {
    private $client = null;
    private $logger = null;

    public function __construct($client, $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Checks for successful authentication
     *
     * @param string $url URL to use
     * @return bool Current user is authenticated or not
     */

    public function checkAuthentication($url = null)
    {
        return $this->client->CheckValidCalDAV($url);
    }

    /**
     * Checks if a calendar is accessible (readable) for current user
     *
     * @param string $href Collection relative URL
     * @return bool Whether the calendar was valid or not
     */
    public function isAccessible($href)
    {
        $info = $this->client->GetCalendarDetailsByURL($href);

        return ($this->client->GetHttpResultCode() == '207');
    }

    /**
     * Finds current user calendar-home-set
     *
     * TODO allow multiple calendar-home-set
     * @return string Calendar home set
     */
    public function findCalendarHomeSet() {
        $res = $this->client->FindCalendarHome();
        return $res[0];
    }


    /**
     * Fetches events for a given resource
     *
     * @param string $href Relative URL to collection
     * @param int $start Start timestamp
     * @param int $end End timestamp
     * @return Array Parsed events
     */
    public function fetchEvents($href, $start, $end)
    {
        $entries = $this->client->GetEvents($start, $end, $href);

        // Bogus CalDAV server
        if (false === $entries) {
            $this->logger->message('ERROR', 'Possible invalid CalDAV server');
        } else {
            $this->logger->message('INTERNALS', 'Received ' . count($entries) . ' entries');
        }

        return $entries;
    }

    /**
     * Retrieves an entry by uid on a given collection
     *
     * @param string $href Relative URL to collection
     * @param string $uid UID of the desired resource
     * @return Array Matching event (or empty array, if not found)
     */
    public function fetchEntryByUid($href, $uid)
    {
        $result = array();
        $entry = $this->client->GetEntryByUid($uid, $href);

        if (!is_array($entry) || count($entry) == 0) {
            $this->logger->message(
                    'INTERNALS', 
                    'Search for UID=' . $uid . ' on calendar ' . $href . ' failed or returned more than one element'
            );
        } else {
            $this->logger->message(
                    'INTERNALS', 
                    'Search for UID=' . $uid . ' on calendar ' . $href . ': found'
            );
            $result = current(array_values($entry));
        }

        return $result;
    }

    /**
     * Deletes a resource from server
     *
     * @param string $href Relative URL to collection/resource
     * @param string $etag ETag for given resource, optional
     * @return mixed true on success, HTTP result code otherwise
     */

    public function deleteResource($href, $etag = null)
    {
        $result = $this->client->DoDELETERequest($href, $etag);

        $this->logger->message(
                'INTERNALS',
                'DELETE on resource ' . $href . ' returned HTTP code ' . $result
        );

        return ($result[0] == '2') ? true : $result;
    }

    /**
     * Puts an iCalendar resource on the server
     *
     * New resources have to be added using etag = '*'
     *
     * @param string $href Relative URL for the resource
     * @param string $icalendar iCalendar text resource
     * @param string $etag ETag for operation
     * @return mixed New ETag on success, or array(HTTP result code) otherwise
     */

    public function putResource($href, $icalendar, $etag = null)
    {
        $new_etag = $this->client->DoPUTRequest($href, $icalendar, $etag);
        $result_code = $this->client->GetHTTPResultCode();

        $this->logger->message(
                'INTERNALS',
                'PUT on resource ' . $href . ' returned HTTP code ' .  $result_code
        );

        return ($result_code[0] == '2') ? $new_etag : array($result_code);
    }



    /**
     * Creates a new calendar inside a principal collection
     *
     * @param string $href Relative URL for the new collection
     * @param Array $props Properties for new calendar
     * @return mixed true on successful creation, HTTP result code otherwise
     */

    public function createCalendar($href, $props = array())
    {
        // Create XML body
        $ns = array(
                'DAV:' => '', 
                'urn:ietf:params:xml:ns:caldav' => 'C',
                'http://apple.com/ns/ical/' => 'ical');
        $xml = new XMLDocument($ns);
        $set = $xml->NewXMLElement('set');
        $prop = $set->NewElement('prop');
        if (isset($props['displayname'])) {
            $xml->NSElement($prop, 'displayname', $props['displayname']);
        }
        if (isset($props['color'])) {
            $xml->NSElement($prop, 'http://apple.com/ns/ical/:calendar-color', $props['color']);
        }

        // TODO: associate timezone? AWL doesn't like <CDATA, 
        // gets replaced by html entity

        $xml_text = $xml->Render('C:mkcalendar', $set, null, 'http://apple.com/ns/ical/:calendar-color');

        $res = $this->client->DoXMLRequest('MKCALENDAR', $xml_text, $href);

        $code = $this->client->GetHTTPResultCode();
        $this->CI->extended_logs->message('INTERNALS', 'MKCALENDAR on '.$href.' returned HTTP ' . $code);

        return ($code[0] == '2') ? true : $code;
    }

    /**
     * Changes properties for a given resource
     *
     * @param string $href Relative URL for the resource
     * @param Array $props New properties
     * @return mixed  true on successful creation, HTTP result code otherwise
     */
    public function changeProperties($href, $props = array())
    {
        // Create XML body
        $ns = array(
                'DAV:' => '', 
                'urn:ietf:params:xml:ns:caldav' => 'C',
                'http://apple.com/ns/ical/' => 'ical');
        $xml = new XMLDocument($ns);
        $set = $xml->NewXMLElement('set');
        $prop = $set->NewElement('prop');
        if (isset($props['displayname'])) {
            $xml->NSElement($prop, 'displayname', $props['displayname']);
        }

        if (isset($props['color'])) {
            $xml->NSElement($prop, 'http://apple.com/ns/ical/:calendar-color', $props['color']);
        }

        // TODO: associate timezone? AWL doesn't like <CDATA, 
        // gets replaced by html entity
        
        $xml_text = $xml->Render('propertyupdate', $set, null, 'http://apple.com/ns/ical/:calendar-color');

        $res = $this->client->DoPROPPATCH($xml_text, $href);
        $code = $this->client->GetHTTPResultCode();

        $this->logger->message('INTERNALS', 'PROPPATCH on ' .  $href . ' returned HTTP code ' . $code);

        return ($res == true) ? true : $code;
    }

    /**
     * Sets an ACL on a resource
     *
     * @param string $href Relative URL to the resource
     * @param Array $share_with List of principals+permissions we want to share this calendar with
     * @return mixed true on success, HTTP code otherwise
     */
    public function setACL($href, $share_with = array())
    {
        // Create XML body
        $xmlbody = $this->generateACLXML($share_with);
        if (false === $xmlbody) {
            $this->logger->message('ERROR', 'Call to setACL() generated invalid XML code. Giving up.');
            // Simulate a 400 code
            return '400';
        }

        $res = $this->client->DoXMLRequest('ACL', $xmlbody, $href);
        $code = $this->client->GetHTTPResultCode();

        $this->logger->message('INTERNALS', 'ACL on ' . $href . ' returned HTTP code ' . $code);

        return ($code[0] == '2') ? true : $code;
    }

    /**
     * Searchs a principal based on passed conditions.
     *
     * @param string $dn Display name
     * @param string $user_address Mail address
     * @param bool $use_or If set to true, combines previous conditions using an 'or' operand
     * @return Array Principals found for the given conditions
     */
    public function principalSearch($dn = null, $user_address = null, $use_or = true)
    {
        $return_results = array();

        // Build XML
        $xml = <<<EOXML
<?xml version="1.0" encoding="utf-8" ?>
<principal-property-search xmlns="DAV:"
EOXML;
        $xml .= ($use_or ? ' test="anyof"' : '') . '>';
        if ($dn !== null) {
            $xml .= '<property-search>';
            $xml .= '<prop><displayname/></prop>';
            $xml .= '<match>' . $dn . '</match></property-search>';
        }

        if ($user_address !== null) {
            $xml .= '<property-search><prop>';
            $xml .= '<C:calendar-user-address-set '
                .'xmlns:C="urn:ietf:params:xml:ns:caldav"/></prop>';
            $xml .= '<match>'.$user_address.'</match></property-search>';
        }

        $xml .= '<prop><displayname/><email/></prop></principal-property-search>';

        // Do request
        $res = $this->client->principal_property_search($xml);

        if (false !== $res) {
            $return_results = $res;
        }

        // Sort by username
        ksort($return_results);

        return $return_results;
    }


    /**
     * Get a list of calendars owned by current user
     *
     * @param Array List of calendar paths to retrieve. If not present or empty array, only own calendars will be retrieved
     * @return Array Calendar list
     */
    public function getCalendars($list = array())
    {
        $cals = array();

        if (count($list) == 0) {
            $cals = $this->client->FindCalendars();
        } else {
            foreach ($list as $wanted) {
                $info = $this->client->GetCalendarDetailsByURL($wanted);
                if (!is_array($info) || count($info) == 0) {
                    // Something went really wrong in this calendar
                    $this->logger->message(
                            'ERROR',
                            'Ignoring calendar ' . $wanted . '. PROPFIND: ' . $this->client->GetHTTPResultCode()
                    );
                } else {
                    $cals = array_merge($cals, $info);
                }
            }
        }

        // Add public URLs
        foreach ($cals as $c) {
            $c->public_url = $this->publicUrl($c->calendar);
        }

        return $cals;
    }

    /**
     * Get the properties of a calendar list
     *
     * @return Prepared data for browser, FALSE on error
     */
    public function get_shared_calendars_info($user, $passwd, $calendar_list) {
        $this->prepare_client($user, $passwd, '');
        
        $tmpcals = array();
        foreach ($calendar_list as $calid => $properties_on_db) {
            $url = $this->build_calendar_url($user, $calid);
            $info = $this->client->GetCalendarDetailsByURL($url);

            if (!is_array($info) || count($info) == 0) {
                // Something went really wrong in this calendar
                $this->CI->extended_logs->message('ERROR', 
                        'Ignoring shared calendar '
                        . $url . '. PROPFIND yielded '
                        . $this->client->GetHttpResultCode());
                continue;
            }

            $properties = $info[$calid];


            // Give priority to previous data (user customizations?)
            $preserve = array('sid', 'user_from', 'color', 'displayname');
            foreach ($preserve as $p) {
                if (isset($properties_on_db[$p])) {
                    $properties->$p = $properties_on_db[$p];
                }
            }

            $properties->shared = TRUE;
            $properties->write_access = $properties_on_db['write_access'];
            $tmpcals[$calid] = $properties;
        }

        return $tmpcals;
    }

    /**
     * Returns the public CalDAV URL for a calendar
     * TODO
     *
     * @param string $href Relative URL for given resource
     * @return string CalDAV public URL
     */
    protected function publicUrl($href)
    {
        return 'TODO'. $href;
    }


    /**
     * Generates a complete ACL to be set on a calendar
     * TODO
     *
     * @param Array $share_with   Array of arrays: [sid?, username, write_access]
     * @return string XML generated document
     */
    private function generateACLXML($share_with = array())
    {
        $ns = array(
                'DAV:' => '', 
                'urn:ietf:params:xml:ns:caldav' => 'C',
                );
        $xml = new XMLDocument($ns);
        $aces = array();

        // Permissions
        $owner_perm = $this->CI->config->item('owner_permissions');
        $r_perm = $this->CI->config->item('read_profile_permissions');
        $rw_perm = $this->CI->config->item('read_write_profile_permissions');
        $other_perm = $this->CI->config->item('default_permissions');

        // Owner permissions
        $aces[] = $this->generateACE($xml, null, $owner_perm, true);

        // User which can access this calendar
        foreach ($share_with as $share) {
            // TODO XXX
            $user_url = $this->build_principal_url($share['username']);
            $aces[] = $this->generateACE($xml, $user_url, ($share['write_access'] == '1' ?  $rw_perm : $r_perm));
        }

        // Other users
        $aces[] = $this->generateACE($xml, null, $other_perm, false);

        return $xml->Render('acl', $aces);
    }

    /**
     * Generates an ACE element
     *
     * @param XMLDocument $xmldoc XML document
     * @param string $principal Principal URL which we are generating the ACE for
     * @param Array $perms CalDAV permissions for this principal
     * @param bool $is_owner If set to true, this ACE will be generated for resource owner
     * @return XMLElement XMLElement to be added to ACL XML
     */
    private function generateACE(&$xmldoc, $principal = null, $perms = array(), $is_owner = false)
    {
        $ace = $xmldoc->NewXMLElement('ace');
        $principal = $ace->NewElement('principal');

        if ($is_owner === true) {
            $principal->NewElement('property')->NewElement('owner');
        } elseif ($principal === null) {
            $principal->NewElement('authenticated');
        } else {
            $principal->NewElement('href', $principal);
        }

        $grant = $ace->NewElement('grant');
        foreach ($perms as $p) {
            $grant->NewElement('privilege')->NewElement($p);
        }

        return $ace;
    }
}
