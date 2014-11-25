<?php

namespace AgenDAV\CalDAV;

use Mockery as m;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Subscriber\Mock as GuzzleMock;
use GuzzleHttp\Subscriber\History as GuzzleHistory;
use AgenDAV\Http\Client as HttpClient;
use AgenDAV\XML\Generator;
use AgenDAV\XML\Parser;
use AgenDAV\Data\Calendar;

/**
 * @author jorge
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var \AgenDAV\Http\Client */
    protected $http_client;

    /** @var \AgenDAV\XML\Generator */
    protected $xml_generator;

    /** @var \AgenDAV\XML\Parser */
    protected $xml_parser;

    /** @var GuzzleHttp\Subscriber\History */
    protected $history;


    public function setUp()
    {
        $this->xml_generator = new Generator();
        $this->xml_parser = new Parser();
        $this->history = new GuzzleHistory();
    }

    public function testCantAuthenticate()
    {
        // #1 Test an authentication failure
        $response = new Response(401);
        $caldav_client = $this->createCalDAVClient($response);

        $this->assertFalse($caldav_client->canAuthenticate(), 'canAuthenticate() works on 4xx/5xx');
        $this->validateCheckAuthenticatedRequests();
    }

    public function testAuthenticateOnNonCalDAVServer()
    {
        $response = new Response(200, []);
        $caldav_client = $this->createCalDAVClient($response);

        $this->assertFalse($caldav_client->canAuthenticate(), 'canAuthenticate() works on non CalDAV servers');
        $this->validateCheckAuthenticatedRequests();
    }

    public function testCanAuthenticate()
    {
        // #1 Test an authentication failure
        $response = new Response(200, ['DAV' => '1, 3, extended-mkcol, access-control, calendarserver-principal-property-search, calendar-access, calendar-proxy']);
        $caldav_client = $this->createCalDAVClient($response);

        $this->assertTrue($caldav_client->canAuthenticate(), 'canAuthenticate() does not work');
        $this->validateCheckAuthenticatedRequests();
    }

    /** @expectedException \UnexpectedValueException */
    public function testGetCurrentUserPrincipalNotFound()
    {
        $body = <<<BODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/"></d:multistatus>
BODY;
        $response = new Response(207, [], Stream::factory($body));
        $caldav_client = $this->createCalDAVClient($response);

        $caldav_client->getCurrentUserPrincipal();
    }

    public function testGetCurrentUserPrincipal()
    {
        $body = <<<BODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">
  <d:response>
    <d:href>/cal.php/</d:href>
    <d:propstat>
      <d:prop>
        <d:current-user-principal>
          <d:href>/cal.php/principals/demo/</d:href>
        </d:current-user-principal>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
</d:multistatus>
BODY;
        $response = new Response(207, [], Stream::factory($body));
        $caldav_client = $this->createCalDAVClient($response);

        $this->assertEquals(
            '/cal.php/principals/demo/',
            $caldav_client->getCurrentUserPrincipal()
        );

        $this->validatePropfindRequest(
            [ '{DAV:}current-user-principal' ],
            null,
            0
        );
    }

    /** @expectedException \UnexpectedValueException */
    public function testGetCalendarHomeSetNotFound()
    {
        $body = <<<BODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/"></d:multistatus>
BODY;
        $response = new Response(207, [], Stream::factory($body));
        $caldav_client = $this->createCalDAVClient($response);

        $caldav_client->getCalendarHomeSet('/principal/url');
    }

    public function testGetCalendarHomeSet()
    {
        $body = <<<BODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">
  <d:response>
    <d:href>/cal.php/principals/demo/</d:href>
    <d:propstat>
      <d:prop>
        <cal:calendar-home-set>
          <d:href>/cal.php/calendars/demo/</d:href>
        </cal:calendar-home-set>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
</d:multistatus>
BODY;
        $response = new Response(207, [], Stream::factory($body));
        $caldav_client = $this->createCalDAVClient($response);

        $calendar_home_set = $caldav_client->getCalendarHomeSet('/principal/url');
        $this->assertEquals(
            '/cal.php/calendars/demo/',
            $calendar_home_set
        );
        $this->validatePropfindRequest(
            [ '{urn:ietf:params:xml:ns:caldav}calendar-home-set' ],
            '/principal/url',
            0
        );
    }

    public function testGetCalendarsRecursive()
    {
        $body = <<<BODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">
  <d:response>
    <d:href>/cal.php/calendars/demo/</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype>
          <d:collection/>
        </d:resourcetype>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <d:displayname/>
        <cs:getctag/>
        <cal:supported-calendar-component-set/>
        <x4:calendar-color xmlns:x4="http://apple.com/ns/ical/"/>
        <x4:calendar-order xmlns:x4="http://apple.com/ns/ical/"/>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/cal.php/calendars/demo/first/</d:href>
    <d:propstat>
      <d:prop>
        <d:displayname>First calendar</d:displayname>
        <cs:getctag>44</cs:getctag>
        <cal:supported-calendar-component-set>
          <cal:comp name="VEVENT"/>
          <cal:comp name="VTODO"/>
        </cal:supported-calendar-component-set>
        <x4:calendar-color xmlns:x4="http://apple.com/ns/ical/">#ff4e50ff</x4:calendar-color>
        <d:resourcetype>
          <d:collection/>
          <cal:calendar/>
        </d:resourcetype>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <x4:calendar-order xmlns:x4="http://apple.com/ns/ical/"/>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/cal.php/calendars/demo/second/</d:href>
    <d:propstat>
      <d:prop>
        <d:displayname>Second calendar</d:displayname>
        <cs:getctag>15</cs:getctag>
        <cal:supported-calendar-component-set>
          <cal:comp name="VEVENT"/>
          <cal:comp name="VTODO"/>
        </cal:supported-calendar-component-set>
        <x4:calendar-color xmlns:x4="http://apple.com/ns/ical/">#3e4147ff</x4:calendar-color>
        <d:resourcetype>
          <d:collection/>
          <cal:calendar/>
        </d:resourcetype>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <x4:calendar-order xmlns:x4="http://apple.com/ns/ical/"/>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/cal.php/calendars/demo/outbox/</d:href>
    <d:propstat>
      <d:prop>
        <d:resourcetype>
          <d:collection/>
          <cal:schedule-outbox/>
        </d:resourcetype>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <d:displayname/>
        <cs:getctag/>
        <cal:supported-calendar-component-set/>
        <x4:calendar-color xmlns:x4="http://apple.com/ns/ical/"/>
        <x4:calendar-order xmlns:x4="http://apple.com/ns/ical/"/>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
</d:multistatus>
BODY;
        $response = new Response(
            207,
            [],
            Stream::factory($body)
        );

        $client = $this->createCalDAVClient($response);
        $calendars = $client->getCalendars('/calendar-home');

        // Only two calendars should be detected
        $this->assertCount(2, $calendars);

        $url_first = '/cal.php/calendars/demo/first/';
        $url_second = '/cal.php/calendars/demo/second/';
        $this->assertArrayHasKey($url_first, $calendars);
        $this->assertArrayHasKey($url_second, $calendars);

        $first_calendar = $calendars[$url_first];
        $this->assertEquals('First calendar', $first_calendar->getProperty(Calendar::DISPLAYNAME));
        $this->assertEquals('44', $first_calendar->getProperty(Calendar::CTAG));
        $this->assertEquals('#ff4e50ff', $first_calendar->getProperty(Calendar::COLOR));

        $second_calendar = $calendars[$url_second];
        $this->assertEquals('Second calendar', $second_calendar->getProperty(Calendar::DISPLAYNAME));
        $this->assertEquals('15', $second_calendar->getProperty(Calendar::CTAG));
        $this->assertEquals('#3e4147ff', $second_calendar->getProperty(Calendar::COLOR));

        $this->validatePropfindRequest(
          [
            '{DAV:}resourcetype',
            '{DAV:}displayname',
            '{http://calendarserver.org/ns/}getctag',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
            '{http://apple.com/ns/ical/}calendar-color',
            '{http://apple.com/ns/ical/}calendar-order',
          ],
          '/calendar-home',
          1
        );
    }

    public function testGetCalendarsNoRecurse()
    {
        $body = <<<BODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">
  <d:response>
    <d:href>/cal.php/calendars/demo/single/</d:href>
    <d:propstat>
      <d:prop>
        <d:displayname>First calendar</d:displayname>
        <cs:getctag>44</cs:getctag>
        <cal:supported-calendar-component-set>
          <cal:comp name="VEVENT"/>
          <cal:comp name="VTODO"/>
        </cal:supported-calendar-component-set>
        <x4:calendar-color xmlns:x4="http://apple.com/ns/ical/">#ff4e50ff</x4:calendar-color>
        <d:resourcetype>
          <d:collection/>
          <cal:calendar/>
        </d:resourcetype>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <x4:calendar-order xmlns:x4="http://apple.com/ns/ical/"/>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
</d:multistatus>
BODY;
        $response = new Response(
            207,
            [],
            Stream::factory($body)
        );

        $client = $this->createCalDAVClient($response);

        $fake_calendar_url = '/cal.php/calendars/demo/single/';
        $calendars = $client->getCalendars($fake_calendar_url, false);

        $this->assertCount(1, $calendars);

        $this->assertArrayHasKey($fake_calendar_url, $calendars);

        $calendar = $calendars[$fake_calendar_url];
        $this->assertEquals('First calendar', $calendar->getProperty(Calendar::DISPLAYNAME));
        $this->assertEquals('44', $calendar->getProperty(Calendar::CTAG));
        $this->assertEquals('#ff4e50ff', $calendar->getProperty(Calendar::COLOR));

        $this->validatePropfindRequest(
          [
            '{DAV:}resourcetype',
            '{DAV:}displayname',
            '{http://calendarserver.org/ns/}getctag',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
            '{http://apple.com/ns/ical/}calendar-color',
            '{http://apple.com/ns/ical/}calendar-order',
          ],
          $fake_calendar_url,
          0
        );

        // Reuse this test for getCalendarByUrl
        $client = $this->createCalDAVClient($response);

        $fake_calendar_url = '/cal.php/calendars/demo/single/';
        $calendar = $client->getCalendarByUrl($fake_calendar_url);

        $this->assertInstanceOf(
          '\AgenDAV\Data\Calendar',
          $calendar
        );
    }

    public function testCreateCalendar()
    {
      $response = new Response(201);
      $client = $this->createCalDAVClient($response);

      $properties = [
        Calendar::DISPLAYNAME => 'Calendar name',
        Calendar::CTAG => 'x',
      ];
      $calendar = new Calendar(
        '/fake/calendar',
        $properties
      );

      $client->createCalendar($calendar);
      $this->validateMkCalendarRequest($calendar);
    }

    public function testUpdateCalendar()
    {
      $response = new Response(200);
      $client = $this->createCalDAVClient($response);

      $properties = [
        Calendar::DISPLAYNAME => 'Calendar name',
        Calendar::CTAG => 'x',
      ];
      $calendar = new Calendar(
        '/fake/calendar',
        $properties
      );

      $client->updateCalendar($calendar);
      $this->validateProppatchRequest($calendar);
    }

    public function testDeleteCalendar()
    {
      $response = new Response(204);
      $client = $this->createCalDAVClient($response);

      $calendar = new Calendar('/fake/calendar');

      $client->deleteCalendar($calendar);
      $this->validateDeleteCalendarRequest($calendar);
    }



    public function testFetchEventsFromCalendar()
    {
      $body = <<<BODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">
  <d:response>
    <d:href>/cal.php/calendars/demo/fake/c160fd13-829d-4d59-96d2-92fc0f9e6787.ics</d:href>
    <d:propstat>
      <d:prop>
        <cal:calendar-data>BEGIN:VCALENDAR
VERSION:2.0
PRODID:-////NONSGML kigkonsult.se iCalcreator 2.14//
CALSCALE:GREGORIAN
BEGIN:VEVENT
UID:c160fd13-829d-4d59-96d2-92fc0f9e6787
DTSTAMP:20141125T084604Z
CLASS:PUBLIC
CREATED:20141125T084604Z
DTSTART;TZID=Europe/Madrid:20141125T094500
DTEND;TZID=Europe/Madrid:20141125T104500
LAST-MODIFIED:20141125T084604Z
SEQUENCE:0
SUMMARY:Test for today
TRANSP:OPAQUE
END:VEVENT
END:VCALENDAR
        </cal:calendar-data>
        <d:getetag>"cf1ba7bcb47ca422f65854470feaeefd"</d:getetag>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
  <d:response>
    <d:href>/cal.php/calendars/demo/fake/e2f43f04-030d-4c79-9c8b-d20c87ca5f9d.ics</d:href>
    <d:propstat>
      <d:prop>
        <cal:calendar-data>BEGIN:VCALENDAR
VERSION:2.0
PRODID:-////NONSGML kigkonsult.se iCalcreator 2.14//
CALSCALE:GREGORIAN
BEGIN:VEVENT
UID:e2f43f04-030d-4c79-9c8b-d20c87ca5f9d
DTSTAMP:20141125T084611Z
CLASS:PUBLIC
CREATED:20141125T084611Z
DTSTART;TZID=Europe/Madrid:20141126T100000
DTEND;TZID=Europe/Madrid:20141126T110000
LAST-MODIFIED:20141125T084617Z
SEQUENCE:1
SUMMARY:Another test
TRANSP:OPAQUE
END:VEVENT
END:VCALENDAR
        </cal:calendar-data>
        <d:getetag>"cf03e087c6bf4f8473f5f76cf17d65fd"</d:getetag>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
</d:multistatus>
BODY;
      $response = new Response(
        207,
        [],
        Stream::factory($body)
      );

      $client = $this->createCalDAVClient($response);

      $calendar = new Calendar('/cal.php/calendars/demo/fake/');

      $start = '20141101T000000Z';
      $end = '20141201T000000Z';
      $events = $client->fetchEventsFromCalendar($calendar, $start, $end);

      $this->assertCount(2, $events);
      $this->assertEquals(
        '/cal.php/calendars/demo/fake/c160fd13-829d-4d59-96d2-92fc0f9e6787.ics',
        $events[0]->getUrl()
      );
      $this->assertEquals('"cf1ba7bcb47ca422f65854470feaeefd"', $events[0]->getEtag());
      $this->assertEquals($calendar, $events[0]->getCalendar());
      $this->assertNotEmpty($events[0]->getContents());

      $this->assertEquals(
        '/cal.php/calendars/demo/fake/e2f43f04-030d-4c79-9c8b-d20c87ca5f9d.ics',
        $events[1]->getUrl()
      );
      $this->assertEquals('"cf03e087c6bf4f8473f5f76cf17d65fd"', $events[1]->getEtag());
      $this->assertEquals($calendar, $events[1]->getCalendar());
      $this->assertNotEmpty($events[1]->getContents());

      $this->validateFetchEventsRequest($calendar, $start, $end);
    }

    /**
     * Create CalDAV client using mocked responses
     */
    protected function createCalDAVClient(Response $response)
    {
        $guzzle = new GuzzleClient();
        $mock = new GuzzleMock([ $response ]);
        $guzzle->getEmitter()->attach($mock);
        $guzzle->getEmitter()->attach($this->history);
        $this->http_client = new HttpClient($guzzle);

        return new Client($this->http_client, $this->xml_generator, $this->xml_parser);
    }


    /**
     * Validates the request generated by a canAuthenticate() method call
     */
    protected function validateCheckAuthenticatedRequests()
    {
        $this->assertCount(1, $this->history);
        $request = $this->history->getLastRequest();
        $this->assertEquals('OPTIONS', $request->getMethod());
    }

    /**
     * Validates a PROPFIND request
     */
    protected function validatePropfindRequest(
        array $properties,
        $url = null,
        $depth = null
    )
    {
        $this->assertCount(1, $this->history);
        $request = $this->history->getLastRequest();
        $this->assertEquals('PROPFIND', $request->getMethod());

        if ($url !== null) {
            $this->assertEquals($url, $request->getUrl());
        }

        if ($depth !== null) {
            $this->assertEquals($depth, $request->getHeader('Depth'));
        }

        $this->assertEquals(
            'application/xml; charset=utf-8',
            $request->getHeader('Content-Type')
        );
        $this->assertEquals(
            $this->xml_generator->propfindBody($properties),
            (string)$request->getBody()
        );
    }

    protected function validateMkCalendarRequest(Calendar $calendar)
    {
        $this->assertCount(1, $this->history);
        $request = $this->history->getLastRequest();
        $this->assertEquals('MKCALENDAR', $request->getMethod());
        $this->assertEquals($calendar->getUrl(), $request->getUrl());
        $this->assertEquals(
            'application/xml; charset=utf-8',
            $request->getHeader('Content-Type')
        );
        $this->assertEquals(
            $this->xml_generator->mkCalendarBody($calendar->getWritableProperties()),
            (string)$request->getBody()
        );
    }

    protected function validateProppatchRequest(Calendar $calendar)
    {
        $this->assertCount(1, $this->history);
        $request = $this->history->getLastRequest();
        $this->assertEquals('PROPPATCH', $request->getMethod());
        $this->assertEquals($calendar->getUrl(), $request->getUrl());
        $this->assertEquals(
            'application/xml; charset=utf-8',
            $request->getHeader('Content-Type')
        );
        $this->assertEquals(
            $this->xml_generator->proppatchBody($calendar->getWritableProperties()),
            (string)$request->getBody()
        );
    }

    protected function validateDeleteCalendarRequest(Calendar $calendar)
    {
        $this->assertCount(1, $this->history);
        $request = $this->history->getLastRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals($calendar->getUrl(), $request->getUrl());
    }

    protected function validateFetchEventsRequest(Calendar $calendar, $start, $end)
    {
        $this->assertCount(1, $this->history);
        $request = $this->history->getLastRequest();
        $this->assertEquals('REPORT', $request->getMethod());
        $this->assertEquals($calendar->getUrl(), $request->getUrl());
        $this->assertEquals(
            'application/xml; charset=utf-8',
            $request->getHeader('Content-Type')
        );
        $this->assertEquals(1, $request->getHeader('Depth'));

        $filter = new TimeRangeFilter($start, $end);
        $this->assertEquals(
            $this->xml_generator->reportBody($filter),
            (string)$request->getBody()
        );
    }
}
