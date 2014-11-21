<?php
namespace AgenDAV\CalDAV;

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

use \AgenDAV\Data\Calendar;

class Client
{
    /** @type AgenDAV\Http\Client   HTTP client used */

    protected $http_client;

    /** @type AgenDAV\XML\Generator XML generator */
    protected $xml_generator;

    /** @type AgenDAV\XML\Parser XML parser */
    protected $xml_parser;


    /**
     * @param \AgenDAV\Http\Client $http_client
     * @param \AgenDAV\XML\Generator $xml_generator
     * @param \AgenDAV\XML\Parser $xml_parser
     */
    public function __construct(
        \AgenDAV\Http\Client $http_client,
        \AgenDAV\XML\Generator $xml_generator,
        \AgenDAV\XML\Parser $xml_parser
    )
    {
        $this->http_client = $http_client;
        $this->xml_generator = $xml_generator;
        $this->xml_parser = $xml_parser;
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
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Invalid authentication
            return false;
        }

        return ($response->hasHeader('DAV') &&
            preg_match('/calendar-access/', $response->getHeader('DAV')));
    }

    /**
     * Retrieves DAV:current-user-principal for the current authenticated
     * user
     *
     * @return string   Principal URL
     */
    public function getCurrentUserPrincipal()
    {
        $body = $this->xml_generator->propfindBody([
            '{DAV:}current-user-principal'
        ]);

        $response = $this->propfind('', 0, $body);

        if (count($response) === 0) {
            throw new \UnexpectedValueException('No current-user-principal was returned by the server!');
        }

        reset($response);
        $result = current($response);

        return $result;
    }

    /**
     * Queries the CalDAV server for the calendar-home-set. It has to be
     * requested on the principal URL
     *
     * @param string $principal_url Principal URL
     * @return string   Calendar home set for given principal
     */
    public function getCalendarHomeSet($principal_url)
    {
        $body = $this->xml_generator->propfindBody([
            '{urn:ietf:params:xml:ns:caldav}calendar-home-set'
        ]);

        $response = $this->propfind($principal_url, 0, $body);

        if (count($response) === 0) {
            throw new \UnexpectedValueException('No calendar-home-set was returned by the server!');
        }

        reset($response);
        $result = current($response);

        return $result;
    }


    /**
     * Gets the list of calendars accessible by current user on a given URL
     *
     * @param string $url   URL
     * @param bool $recurse Whether to recurse (Depth: 1) or not (Depth: 0).
     *                       Default to true
     * @return array
     */
    public function getCalendars($url, $recurse = true)
    {
        $body = $this->xml_generator->propfindBody([
            '{DAV:}resourcetype',
            '{DAV:}displayname',
            '{http://calendarserver.org/ns/}getctag',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
            '{http://apple.com/ns/ical/}calendar-color',
            '{http://apple.com/ns/ical/}calendar-order',
        ]);

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
     * @param \AgenDAV\Data\Calendar    Found calendar
     * @throws \UnexpectedValueException In case the server replies with a 2xx code but
     *                                   valid calendars are not found
     */
    public function getCalendarByUrl($url)
    {
        $result = $this->getCalendars($url, false);
        if (count($result) === 0) {
            throw new \UnexpectedValueException('Calendar not found at ' . $url);
        }

        reset($result);
        return current($result);
    }

    /**
     * Creates a calendar collection
     *
     * @param AgenDAV\Data\Calendar $calendar   Calendar that we want to create
     * @return void
     */
    public function createCalendar(\AgenDAV\Data\Calendar $calendar)
    {
        $calendar_properties = $calendar->getWritableProperties();
        $body = $this->xml_generator->mkCalendarBody($calendar_properties);
        $this->http_client->setContentTypeXML();

        $this->http_client->request('MKCALENDAR', $calendar->getUrl(), $body);
    }

    /**
     * Modifies an existing calendar
     *
     * @param \AgenDAV\Data\Calendar $calendar
     * @return void
     */
    public function updateCalendar(\AgenDAV\Data\Calendar $calendar)
    {
        $calendar_properties = $calendar->getWritableProperties();
        $body = $this->xml_generator->proppatchBody($calendar_properties);
        $this->http_client->setContentTypeXML();

        $this->http_client->request('PROPPATCH', $calendar->getUrl(), $body);
    }

    /**
     * Deletes a calendar from the server
     *
     * @param \AgenDAV\Data\Calendar $calendar
     * @return void
     */
    public function deleteCalendar(\AgenDAV\Data\Calendar $calendar)
    {
        $this->http_client->request('DELETE', $calendar->getUrl());
    }

    /**
     * Fetches all events from a calendar that are in the range of [start, end)
     *
     * @param \AgenDAV\Data\Calendar $calendar
     * @param string $start UTC start time filter, based on ISO8601: 20141120T230000Z
     * @param string $end UTC end time filter, based on ISO8601: 20141121T230000Z
     * @return array Associative array of events:
     *               [ 'resource1.ics' => [ properties ],
     *                 'resource2.ics' => [ properties ],
     *                 ...
     *               ]
     */
    public function fetchEventsFromCalendar(\AgenDAV\Data\Calendar $calendar, $start, $end)
    {
        $time_range_filter = new TimeRangeFilter($start, $end);
        return $this->report($calendar->getUrl(), $time_range_filter);
    }

    /**
     * Fetches the event that has the specified UID
     *
     * @param \AgenDAV\Data\Calendar $calendar
     * @param string $uid Event UID
     * @return array Associative array of properties from event:
     *               [ '{DAV:}getetag' => '...',
     *                 '{urn:ietf:params:xml:ns:caldav}calendar-data' => 'BEGIN:...',
     *                 ...
     *               ]
     */
    public function fetchEventByUid(\AgenDAV\Data\Calendar $calendar, $uid)
    {
        $uid_filter = new UidFilter($uid);
        $result = $this->report($calendar->getUrl(), $uid_filter);

        if (count($result) === 0) {
            throw new \UnexpectedValueException('Event '.$uid.' not found at ' . $calendar->getUrl());
        }

        reset($result);
        $href = current(array_keys($result));
        $event = current($result);
        $event['href'] = $href;

        return $event;
    }

    /**
     * Issues a PROPFIND and parses the response
     *
     * @param string $url   URL
     * @param int $depth   Depth header
     * @param string $body  Request body
     * @result array key-value array, where keys are paths and properties are values
     */
    public function propfind($url, $depth, $body)
    {
        $this->http_client->setHeader('Depth', $depth);
        $this->http_client->setContentTypeXML();
        $response = $this->http_client->request('PROPFIND', $url, $body);

        $contents = (string)$response->getBody();
        $single_element_expected = ($depth === 0);
        $result = $this->xml_parser->extractPropertiesFromMultistatus($contents, $single_element_expected);

        return $result;
    }

    /**
     * Issues a REPORT and parses the response
     *
     * @param string $url   URL
     * @param string \AgenDAV\CalDAV\ComponentFilter DOMElement to be added as
     *                                               a filter for the report
     * @result array key-value array, where keys are paths and properties are values
     */
    public function report($url, ComponentFilter $filter)
    {
        $this->http_client->setHeader('Depth', 1);
        $this->http_client->setContentTypeXML();
        $body = $this->xml_generator->reportBody($filter);
        $response = $this->http_client->request('REPORT', $url, $body);

        $contents = (string)$response->getBody();
        $result = $this->xml_parser->extractPropertiesFromMultistatus($contents);

        return $result;
    }

    /**
     * Puts a VCALENDAR text object on the given URL
     *
     * @param string $url
     * @param string $body VCALENDAR body
     * @param string $etag Optional etag to avoid overwriting an updated calendar
     *                     object
     * @return Guzzle\Http\Message\Response
     */
    public function putEvent($url, $body, $etag = null)
    {
        $this->http_client->setContentTypeiCalendar();

        // New event, so it should not overwrite any existing events
        if ($etag === null) {
            $this->http_client->setHeader('If-None-Match', '*');
        } else {
            $this->http_client->setHeader('If-Match', $etag);
        }

        return $this->http_client->request('PUT', $url, $body);
    }

    /**
     * Deletes a VCALENDAR text object on the given URL
     *
     * @param string $url
     * @param string $etag Optional etag to avoid deleting an updated calendar
     *                     object
     * @return Guzzle\Http\Message\Response
     */
    public function deleteEvent($url, $etag = null)
    {
        // New event, so it should not overwrite any existing events
        if ($etag !== null) {
            $this->http_client->setHeader('If-Match', $etag);
        }
        return $this->http_client->request('DELETE', $url);
    }
}
