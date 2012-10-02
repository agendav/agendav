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

use AgenDAV\Version;

/**
 * AgenDAV CalDAV client 
 */
class CURLClient extends \CalDAVClient implements ICalDAVClient
{
    /**
     * Current user
     *
     * @var Object
     * @access private
     */
    private $app_user;

    /**
     * URL generator manager 
     *
     * @var IURLGenerator
     * @access private
     */
    private $urlgenerator;

    /**
     * Log manager 
     *
     * @var Object
     * @access private
     */
    private $logger;

    /**
     * Creates a new CalDAV client
     *
     * @param Object $app_user Current user
     * @param IURLGenerator $urlgenerator URL generator
     * @param Object $logger Log manager
     * @param string $version AgenDAV version
     * @access public
     * @return void
     */
    public function __construct($app_user, IURLGenerator $urlgenerator, $logger, $version)
    {
        $this->app_user = $app_user;
        $this->urlgenerator = $urlgenerator;
        $this->logger = $logger;

        // TODO auth options
        parent::__construct(
            $this->urlgenerator->getBaseURL(),
            $this->app_user->getUserName(),
            $this->app_user->getPasswd()
        );

        $this->PrincipalURL($this->urlgenerator->generatePrincipal($this->app_user->getUserName()));
        $this->CalendarHomeSet($this->urlgenerator->generateCalendarHomeSet($this->app_user->getUserName()));
        $this->SetUserAgent('AgenDAV v' . $version);
    }

    /**
     * Checks current user authentication against provided 
     * 
     * @param string $url 
     * @access public
     * @return boolean
     */
    public function checkAuthentication($url = '')
    {
        return $this->CheckValidCalDAV($url);
    }

    /**
     * Checks if provided resource is accesible for current user 
     * 
     * @param string $href 
     * @access public
     * @return boolean
     */
    public function isAccessible($href)
    {
        $info = $this->GetCalendarDetailsByURL($href);

        return ($this->GetHttpResultCode() == '207');
    }

    /**
     * Retrieves calendars from the given list 
     * 
     * @param Array $list List of hrefs
     * @access public
     * @return Array
     */
    public function getCalendars($list = null)
    {
        $cals = array();

        if (null === $list) {
            $cals = $this->FindCalendars();
        } else {
            foreach ($list as $wanted) {
                $info = $this->GetCalendarDetailsByURL($wanted);
                if (!is_array($info) || count($info) == 0) {
                    // Something went really wrong in this calendar
                    $this->logger->message(
                            'ERROR',
                            'Ignoring calendar ' . $wanted . '. PROPFIND: ' . $this->GetHTTPResultCode()
                    );
                } else {
                    $cals = array_merge($cals, $info);
                }
            }
        }

        // Add public URLs
        foreach ($cals as $c) {
            $c->public_url = 'TODO';
        }

        return $cals;
    }

    /**
     * Gets current events from given resource
     * 
     * @param string $href Resource URL/path
     * @param int $start Starting timestamp
     * @param int $end End timestamp
     * @access public
     * @return Array
     */
    public function fetchEvents($href, $start, $end)
    {
        $entries = $this->GetEvents($start, $end, $href);

        // Bogus CalDAV server
        if (false === $entries) {
            $this->logger->message('ERROR', 'Possible invalid CalDAV server');
        } else {
            $this->logger->message('INTERNALS', 'Received ' . count($entries) . ' entries');
        }

        return $entries;
    }

    /**
     * Finds an entry using its UID 
     * 
     * @param string $href URL/path
     * @param string $uid Element UID
     * @access public
     * @return Array
     */
    public function fetchEntryByUID($href, $uid)
    {
        $result = array();
        $entry = $this->GetEntryByUid($uid, $href);

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
     * Puts a resource on the server 
     * 
     * @param string $href URL/path
     * @param string $data Text data to be put (iCalendar)
     * @param string $etag Last known Etag for this resource
     * @access public
     * @return string New Etag on success, HTTP result code otherwise
     */
    public function putResource($href, $data, $etag = null)
    {
        $new_etag = $this->DoPUTRequest($href, $icalendar, $etag);
        $result_code = $this->GetHTTPResultCode();

        $this->logger->message(
                'INTERNALS',
                'PUT on resource ' . $href . ' returned HTTP code ' .  $result_code
        );

        return ($result_code[0] == '2') ? $new_etag : array($result_code);
    }

    /**
     * Deletes a resource on the server 
     * 
     * @param string $href URL/path
     * @param string $etag Last known Etag for this resource
     * @access public
     * @return mixed true on success, HTTP result code otherwise
     */
    public function deleteResource($href, $etag = null)
    {
        $result = $this->DoDELETERequest($href, $etag);

        $this->logger->message(
                'INTERNALS',
                'DELETE on resource ' . $href . ' returned HTTP code ' . $result
        );

        return ($result[0] == '2') ? true : $result;
    }

    /**
     * Changes properties for a given resource using PROPPATCH
     *
     * @param \AgenDAV\Data\CalendarInfo $calendar Calendar resource to be modified
     * @return mixed  true on successful creation, HTTP result code otherwise
     */
    public function changeResource(\AgenDAV\Data\CalendarInfo $calendar)
    {
        // Create XML body
        $ns = array(
                'DAV:' => '', 
                'urn:ietf:params:xml:ns:caldav' => 'C',
                'http://apple.com/ns/ical/' => 'ical');
        $xml = new \XMLDocument($ns);
        $set = $xml->NewXMLElement('set');
        $prop = $set->NewElement('prop');
        $xml->NSElement($prop, 'displayname', $calendar->displayname);
        $xml->NSElement($prop, 'http://apple.com/ns/ical/:calendar-color', $calendar->color);

        // TODO: associate timezone? AWL doesn't like <CDATA, 
        // gets replaced by html entity
        
        $xml_text = $xml->Render('propertyupdate', $set, null, 'http://apple.com/ns/ical/:calendar-color');

        $res = $this->DoPROPPATCH($xml_text, $calendar->url);
        $code = $this->GetHTTPResultCode();

        $this->logger->message('INTERNALS', 'PROPPATCH on ' .  $calendar->url . ' returned HTTP code ' . $code);

        return ($res == true) ? true : $code;
    }

    /**
     * Creates a new calendar inside a principal collection
     *
     * @param \AgenDAV\Data\CalendarInfo $calendar New calendar to be added
     * @return mixed true on successful creation, HTTP result code otherwise
     */
    public function createCalendar(\AgenDAV\Data\CalendarInfo $calendar)
    {
        // Create XML body
        $ns = array(
                'DAV:' => '', 
                'urn:ietf:params:xml:ns:caldav' => 'C',
                'http://apple.com/ns/ical/' => 'ical');
        $xml = new \XMLDocument($ns);
        $set = $xml->NewXMLElement('set');
        $prop = $set->NewElement('prop');
        $xml->NSElement($prop, 'displayname', $calendar->displayname);
        $xml->NSElement($prop, 'http://apple.com/ns/ical/:calendar-color', $calendar->color);

        // TODO: associate timezone? AWL doesn't like <CDATA, 
        // gets replaced by html entity

        $xml_text = $xml->Render('C:mkcalendar', $set, null, 'http://apple.com/ns/ical/:calendar-color');

        $res = $this->DoXMLRequest('MKCALENDAR', $xml_text, $calendar->url);

        $code = $this->GetHTTPResultCode();
        $this->logger->message('INTERNALS', 'MKCALENDAR on '.$calendar->url.' returned HTTP ' . $code);

        return ($code[0] == '2') ? true : $code;
    }

    /**
     * Sets an ACL on a resource
     *
     * @param string $href Relative URL to the resource
     * @param Array $share_with List of principals+permissions we want to share this calendar with
     * @return mixed true on success, HTTP code otherwise
     */
    public function setACL($href, $acls)
    {
        return '400';
    }

    /**
     * Searchs a principal based on passed conditions.
     *
     * @param string $dn Display name
     * @param string $user_address Mail address
     * @param bool $use_or If set to true, combines previous conditions using an 'or' operand
     * @return Array Principals found for the given conditions
     */
    public function principalSearch($dn, $user_address, $use_or)
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
        $res = $this->principal_property_search($xml);

        if (false !== $res) {
            $return_results = $res;
        }

        // Sort by username
        ksort($return_results);

        return $return_results;
    }



}
