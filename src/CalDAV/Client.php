<?php
namespace AgenDAV\CalDAV;

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

use \AgenDAV\CalDAV\Resource\Calendar;
use \AgenDAV\CalDAV\Resource\CalendarObject;
use \AgenDAV\Data\Principal;
use \AgenDAV\CalDAV\Share\ACL;
use \AgenDAV\CalDAV\Filter\Uid;
use \AgenDAV\CalDAV\Filter\TimeRange;

class Client
{
    /** @type \AgenDAV\Http\Client   HTTP client used */

    protected $http_client;

    /** @type \AgenDAV\XML\Toolkit XML toolkit  */
    protected $xml_toolkit;

    /** @type \AgenDAV\Event\Parser Event parser */
    protected $event_parser;


    /**
     * @param \AgenDAV\Http\Client $http_client
     * @param \AgenDAV\XML\Toolkit $xml_toolkit
     * @param \AgenDAV\Event\Parser $event_parser
     */
    public function __construct(
        \AgenDAV\Http\Client $http_client,
        \AgenDAV\XML\Toolkit $xml_toolkit,
        \AgenDAV\Event\Parser $event_parser
    )
    {
        $this->http_client = $http_client;
        $this->xml_toolkit = $xml_toolkit;
        $this->event_parser = $event_parser;
    }

    /**
     * Checks if the HTTP client can access the configured base URL by
     * sending an OPTIONS request. It will fail if a) provided credentials are
     * not valid or b) can't find 'calendar-access' in the DAV header
     *
     * @return boolean
     */
    public function canAuthenticate()
    {
        try {
            $response = $this->http_client->request('OPTIONS', '');
        } catch (\AgenDAV\Exception\NotAuthenticated $e) {
            // Invalid authentication
            return false;
        }

        return ($response->hasHeader('DAV') &&
            false !== strpos($response->getHeaderLine('DAV'), "calendar-access"));
    }

    /**
     * Retrieves DAV:current-user-principal for the current authenticated
     * user
     *
     * @return string   Principal URL
     * @throws \AgenDAV\Exception\NotFound if no current-user-principal is returned
     */
    public function getCurrentUserPrincipal()
    {
        $body = $this->xml_toolkit->generateRequestBody(
            'PROPFIND',
            [ '{DAV:}current-user-principal' ]
        );

        $response = $this->propfind('', 0, $body);

        if (count($response) === 0 || !isset($response['{DAV:}current-user-principal'])) {
            throw new \AgenDAV\Exception\NotFound('No current-user-principal was returned by the server!');
        }

        reset($response);
        $response = current($response);
        $result = $response->getHref();

        return $result;
    }

    /**
     * Queries the CalDAV server for the calendar-home-set. It will be
     * requested on the principal URL
     *
     * @param \AgenDAV\Data\Principal $principal User principal
     *
     * @return string Calendar home set for given principal
     *
     * @throws \AgenDAV\Exception\NotFound if no calendar-home-set is returned
     */
    public function getCalendarHomeSet(Principal $principal)
    {
        $body = $this->xml_toolkit->generateRequestBody(
            'PROPFIND',
            [ '{urn:ietf:params:xml:ns:caldav}calendar-home-set' ]
        );

        $url = $principal->getUrl();
        $response = $this->propfind($url, 0, $body);

        if (count($response) === 0 || !isset($response['{urn:ietf:params:xml:ns:caldav}calendar-home-set'])) {
            throw new \AgenDAV\Exception\NotFound('No calendar-home-set was returned by the server!');
        }

        reset($response);
        $response = current($response);
        $result = $response->getHref();

        return $result;
    }


    /**
     * Gets the list of calendars accessible by current user on a given URL
     *
     * @param string $url   URL
     * @param bool $recurse Whether to recurse (Depth: 1) or not (Depth: 0).
     *                       Default to true
     * @return array        Associative array, [url => Calendar]
     */
    public function getCalendars($url, $recurse = true)
    {
        $body = $this->xml_toolkit->generateRequestBody(
            'PROPFIND',
            [
            '{DAV:}resourcetype',
            '{DAV:}displayname',
            '{http://calendarserver.org/ns/}getctag',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
            '{http://apple.com/ns/ical/}calendar-color',
            '{http://apple.com/ns/ical/}calendar-order',
            ]
        );

        $response = $this->propfind($url, $recurse ? 1 : 0, $body);

        // Adapt the non recursive case
        if (!$recurse) {
            $response = [ $url => $response ];
        }

        $calendars = [];
        foreach ($response as $href => $properties) {
            if (!isset($properties['{DAV:}resourcetype'])) {
                continue;
            }

            if ($properties['{DAV:}resourcetype']->is('{urn:ietf:params:xml:ns:caldav}calendar')) {
                $calendars[$href] = new Calendar($href, $properties);
            }
        }

        return $calendars;
    }

    /**
     * Gets a calendar details
     *
     * @param string $url   URL
     *
     * @return Calendar
     *
     * @throws \AgenDAV\Exception\NotFound In case the server replies with a 2xx code but
     *                                     no valid calendars are found
     */
    public function getCalendarByUrl($url)
    {
        $result = $this->getCalendars($url, false);
        if (count($result) === 0) {
            throw new \AgenDAV\Exception\NotFound('Calendar not found at ' . $url);
        }

        reset($result);
        return current($result);
    }

    /**
     * Creates a calendar collection
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar   Calendar that we want to create
     * @return void
     */
    public function createCalendar(Calendar $calendar)
    {
        $calendar_properties = $calendar->getWritableProperties();
        $body = $this->xml_toolkit->generateRequestBody(
            'MKCALENDAR',
            $calendar_properties
        );
        $this->http_client->setContentTypeXML();

        $this->http_client->request('MKCALENDAR', $calendar->getUrl(), $body);
    }

    /**
     * Modifies an existing calendar
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar
     * @return void
     */
    public function updateCalendar(Calendar $calendar)
    {
        $calendar_properties = $calendar->getWritableProperties();
        $body = $this->xml_toolkit->generateRequestBody(
            'PROPPATCH',
            $calendar_properties
        );
        $this->http_client->setContentTypeXML();

        $this->http_client->request('PROPPATCH', $calendar->getUrl(), $body);
    }

    /**
     * Deletes a calendar from the server
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar
     * @return void
     */
    public function deleteCalendar(Calendar $calendar)
    {
        $this->http_client->request('DELETE', $calendar->getUrl());
    }

    /**
     * Fetches all objects from a calendar that are in the range of [start, end)
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar
     * @param string $start UTC start time filter, based on ISO8601: 20141120T230000Z
     * @param string $end UTC end time filter, based on ISO8601: 20141121T230000Z
     * @return array Array of CalendarObject
     */
    public function fetchObjectsOnCalendar(Calendar $calendar, $start, $end)
    {
        $time_range_filter = new TimeRange($start, $end);
        $xml_body = $this->xml_toolkit->generateRequestBody('REPORT-CALENDAR', $time_range_filter);
        $data = $this->report($calendar->getUrl(), $xml_body);

        return $this->buildObjectCollection($data, $calendar);
    }

    /**
     * Fetches all objects from a subscribed calendar
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar
     * @return array Array of CalendarObject
     */
    public function fetchObjectsOnSubscribedCalendar(Calendar $calendar)
    {
        $data = $this->get($calendar->getUrl());

        return $this->buildObjectCollection($data, $calendar);
    }

    /**
     * Fetches the calendar object that has the specified UID
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar
     * @param string $uid Calendar object UID
     * @return \AgenDAV\CalDAV\Resource\CalendarObject
     * @throws \AgenDAV\Exception\NotFound if calendar object is not found
     */
    public function fetchObjectByUid(Calendar $calendar, $uid)
    {
        $uid_filter = new Uid($uid);
        $xml_body = $this->xml_toolkit->generateRequestBody('REPORT-CALENDAR', $uid_filter);
        $data = $this->report($calendar->getUrl(), $xml_body);

        if (count($data) === 0) {
            throw new \AgenDAV\Exception\NotFound('Object '.$uid.' not found at ' . $calendar->getUrl());
        }

        $result = $this->buildObjectCollection($data, $calendar);

        reset($result);
        $calendar_object = current($result);

        return $calendar_object;
    }

    /**
     * Puts an calendar object on the CalDAV server, inside its parent collection
     *
     * @param \AgenDAV\CalDAV\Resource\CalendarObject $calendar_object
     * @return \GuzzleHttp\Psr7\Response
     */
    public function uploadCalendarObject(CalendarObject $calendar_object)
    {
        $this->http_client->setContentTypeiCalendar();

        $etag = $calendar_object->getEtag();
        $url = $calendar_object->getUrl();
        $body = $calendar_object->getRenderedEvent();

        // New object, so it should not overwrite any existing objects
        if ($etag === null) {
            $this->http_client->setHeader('If-None-Match', '*');
        } else {
            $this->http_client->setHeader('If-Match', $etag);
        }

        return $this->http_client->request('PUT', $url, $body);
    }

    /**
     * Deletes a calendar object from the CalDAV server
     *
     * @param CalendarObject $calendar_object
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function deleteCalendarObject(CalendarObject $calendar_object)
    {
        $etag = $calendar_object->getEtag();
        $url = $calendar_object->getUrl();

        // Existing object, so it should not delete without checking ETags
        if ($etag !== null) {
            $this->http_client->setHeader('If-Match', $etag);
        }
        return $this->http_client->request('DELETE', $url);
    }

    /**
     * Sets an ACL on a calendar
     *
     * @param \AgenDAV\CalDAV\Resource\Calendar $calendar
     * @param \AgenDAV\CalDAV\Share\ACL $acl
     * @return \GuzzleHttp\Psr7\Response
     */
    public function applyACL(Calendar $calendar, ACL $acl)
    {
        $url = $calendar->getUrl();
        $this->http_client->setContentTypeXML();
        $body = $this->xml_toolkit->generateRequestBody('ACL', $acl);

        return $this->http_client->request('ACL', $url, $body);
    }

    /**
     * Issues a PROPFIND and parses the response
     *
     * @param string $url   URL
     * @param int $depth   Depth header
     * @param string $body  Request body
     *
     * @return array key-value array, where keys are paths and properties are values
     */
    public function propfind($url, $depth, $body)
    {
        $this->http_client->setHeader('Depth', $depth);
        $this->http_client->setContentTypeXML();
        $response = $this->http_client->request('PROPFIND', $url, $body);

        $contents = (string)$response->getBody();
        $single_element_expected = ($depth === 0);
        $result = $this->xml_toolkit->parseMultistatus($contents, $single_element_expected);

        return $result;
    }

    /**
     * Issues a REPORT and parses the response
     *
     * @param string $url   URL
     * @param string $body   Request body
     * @param int $depth Depth header for this request. Default value: 1
     *
     * @return array key-value array, where keys are paths and properties are values
     */
    public function report($url, $body, $depth = 1)
    {
        $this->http_client->setHeader('Depth', $depth);
        $this->http_client->setContentTypeXML();
        $response = $this->http_client->request('REPORT', $url, $body);

        $contents = (string)$response->getBody();
        $result = $this->xml_toolkit->parseMultistatus($contents);

        return $result;
    }

    /**
     * Issues a GET and parses the response
     *
     * @param string $url   URL
     * @param string $body  Request body
     * @param int    $depth Depth header for this request. Default value: 1
     * @result array key-value array, where keys are paths and properties are values
     */
    public function get($url)
    {
        $response = $this->http_client->request('GET', $url);

        $contents = (string)$response->getBody();

        $result = [
            $url=>[
                "{urn:ietf:params:xml:ns:caldav}calendar-data"=>$contents
            ]
        ];

        return $result;
    }



    /**
     * Converts a pre-parsed REPORT response to an array of CalendarObject
     *
     * @param array $raw_data Data returned by report()
     * @param Calendar $calendar Calendar these objects come from
     *
     * @return CalendarObject[]
     */
    protected function buildObjectCollection(array $raw_data, Calendar $calendar)
    {
        $result = [];

        foreach ($raw_data as $url => $data) {
            $event = $this->event_parser->parse($data[CalendarObject::DATA]);
            $object = new CalendarObject($url, $event);
            $object->setCalendar($calendar);
            if (isset($data[CalendarObject::ETAG])) {
                $object->setEtag($data[CalendarObject::ETAG]);
            }

            $result[] = $object;
        }

        return $result;
    }
}
