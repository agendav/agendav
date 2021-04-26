<?php

namespace AgenDAV\CalDAV;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use AgenDAV\Http\Client as HttpClient;
use AgenDAV\XML\Toolkit;
use AgenDAV\XML\Generator;
use AgenDAV\XML\Parser;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\CalDAV\Share\Permissions;
use AgenDAV\CalDAV\Share\ACL;
use AgenDAV\Event\Parser as EventParser;
use AgenDAV\CalDAV\Filter\Uid;
use AgenDAV\CalDAV\Filter\TimeRange;
use AgenDAV\Data\Principal;
use PHPUnit\Framework\TestCase;

/**
 * @author jorge
 */
class ClientTest extends TestCase
{
    /** @var \AgenDAV\Http\Client */
    protected $http_client;

    /** @var \AgenDAV\XML\Generator */
    protected $xml_generator;

    /** @var array */
    protected $history;

    /** @var \AgenDAV\XML\Parser */
    protected $xml_parser;

    /** @var AgenDAV\Event\Parser */
    protected $event_parser;


    public function setUp()
    {
        $this->xml_generator = new Generator();
        $this->xml_parser = new Parser();
        $this->event_parser = $this->createMock(\AgenDAV\Event\Parser::class);
        $this->history = [];
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

    /** @expectedException \AgenDAV\Exception\NotFound */
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

    /** @expectedException \AgenDAV\Exception\NotFound */
    public function testGetCalendarHomeSetNotFound()
    {
        $body = <<<BODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/"></d:multistatus>
BODY;
        $response = new Response(207, [], Stream::factory($body));
        $caldav_client = $this->createCalDAVClient($response);

        $caldav_client->getCalendarHomeSet(new Principal('/principal/url'));
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

        $calendar_home_set = $caldav_client->getCalendarHomeSet(new Principal('/principal/url'));
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
          '\AgenDAV\CalDAV\Resource\Calendar',
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



    public function testFetchObjectsFromCalendar()
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

      $this->event_parser
        ->expects($this->exactly(2))
        ->method('parse')
        ->willReturn(
          $this->createMock(\AgenDAV\Event::class),
          $this->createMock(\AgenDAV\Event::class)
        );

      $client = $this->createCalDAVClient($response);

      $calendar = new Calendar('/cal.php/calendars/demo/fake/');

      $start = '20141101T000000Z';
      $end = '20141201T000000Z';
      $objects = $client->fetchObjectsOnCalendar($calendar, $start, $end);

      $this->assertCount(2, $objects);
      $this->assertEquals(
          '/cal.php/calendars/demo/fake/c160fd13-829d-4d59-96d2-92fc0f9e6787.ics',
          $objects[0]->getUrl()
      );
      $this->assertEquals('"cf1ba7bcb47ca422f65854470feaeefd"', $objects[0]->getEtag());
      $this->assertEquals($calendar, $objects[0]->getCalendar());
      $this->assertInstanceOf('\AgenDAV\Event', $objects[0]->getEvent());

      $this->assertEquals(
          '/cal.php/calendars/demo/fake/e2f43f04-030d-4c79-9c8b-d20c87ca5f9d.ics',
          $objects[1]->getUrl()
      );
      $this->assertEquals('"cf03e087c6bf4f8473f5f76cf17d65fd"', $objects[1]->getEtag());
      $this->assertEquals($calendar, $objects[1]->getCalendar());
      $this->assertInstanceOf('\AgenDAV\Event', $objects[1]->getEvent());

      $this->validateFetchObjectsRequest($calendar, new TimeRange($start, $end));
    }

    /**
     * @expectedException \AgenDAV\Exception\NotFound
     */
    public function testFetchObjectByUidNonExistant()
    {
        $body = <<<BODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/"/>
BODY;
        $response = new Response(
            207,
            [],
            Stream::factory($body)
        );

        $client = $this->createCalDAVClient($response);
        $calendar = new Calendar('/cal.php/calendars/demo/fake');

        $client->fetchObjectByUid($calendar, 'xxxx');
    }

    public function testFetchEventByUidOK()
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
</d:multistatus>
BODY;
        $response = new Response(
            207,
            [],
            Stream::factory($body)
        );

        $this->event_parser
          ->expects($this->once())
          ->method('parse')
          ->willReturn(
            $this->createMock(\AgenDAV\Event::class)
          );

        $client = $this->createCalDAVClient($response);
        $calendar = new Calendar('/cal.php/calendars/demo/fake');

        $object = $client->fetchObjectByUid($calendar, 'c160fd13-829d-4d59-96d2-92fc0f9e6787');

        $this->assertEquals('"cf1ba7bcb47ca422f65854470feaeefd"', $object->getEtag());
        $this->assertEquals($calendar, $object->getCalendar());
        $this->assertInstanceOf('\AgenDAV\Event', $object->getEvent());

        $this->validateFetchObjectsRequest($calendar, new Uid('c160fd13-829d-4d59-96d2-92fc0f9e6787'));
    }

    public function testPutObjectNew()
    {
        $response = new Response(201);
        $client = $this->createCalDAVClient($response);

        // Twice: the first one for the upload, and the second one for
        // the validity check
        $event = $this->createMock(\AgenDAV\Event::class);
        $event
          ->expects($this->exactly(2))
          ->method('render')
          ->willReturn('iCalendar resource');


        $object = new CalendarObject('/url', $event);

        $client->uploadCalendarObject($object);
        $this->validatePutObjectRequest($object);
    }

    public function testPutObjectExisting()
    {
        $response = new Response(200);
        $client = $this->createCalDAVClient($response);

        // Twice: the first one for the upload, and the second one for
        // the validity check
        $event = $this->createMock(\AgenDAV\Event::class);
        $event
          ->expects($this->exactly(2))
          ->method('render')
          ->willReturn('iCalendar resource');

        $object = new CalendarObject('/url', $event);
        $object->setEtag('test_etag');

        $client->uploadCalendarObject($object);
        $this->validatePutObjectRequest($object);
    }

    public function testDeleteObjectWithEtag()
    {
        $response = new Response(200);
        $client = $this->createCalDAVClient($response);

        $object = new CalendarObject('/url');
        $object->setEtag('test_etag');

        $client->deleteCalendarObject($object);
        $this->validateDeleteObjectRequest($object);
    }

    public function testACL()
    {
        $response = new Response(200);
        $client = $this->createCalDAVClient($response);

        $acl = $this->createMock(\AgenDAV\CalDAV\Share\ACL::class);
        $acl->method('getOwnerPrivileges')
          ->willReturn([ '{DAV:}all' ]);

        $acl->method('getDefaultPrivileges')
          ->willReturn(['{DAV:}minimal']);

        $acl->method('getGrantsPrivileges')
          ->willReturn([
            '/u1' => ['{DAV:}write'],
            '/u2' => ['{DAV:}read'],
          ]);

        $calendar = new Calendar('/url');

        $client->applyACL($calendar, $acl);
        $this->validateACLRequest($calendar, $acl);
    }

    /**
     * Create CalDAV client using mocked responses
     */
    protected function createCalDAVClient(Response $response)
    {
        $mock = new MockHandler([ $response ]);
        $handler_stack = HandlerStack::create($mock);
        $handler_stack->push(Middleware::history($this->history));

        $guzzle = new GuzzleClient(['handler' => $handler_stack]);
        $this->http_client = new HttpClient($guzzle);

        $xml_toolkit = new Toolkit($this->xml_parser, $this->xml_generator);
        return new Client($this->http_client, $xml_toolkit, $this->event_parser);
    }


    /**
     * Validates the request generated by a canAuthenticate() method call
     */
    protected function validateCheckAuthenticatedRequests()
    {
        $this->getAndValidateLastRequest('OPTIONS');
    }

    /**
     * Validates a PROPFIND request
     */
    protected function validatePropfindRequest(array $properties, $url = null, $depth = null)
    {
        $request = $this->getAndValidateLastRequest('PROPFIND');

        if ($url !== null) {
            $this->assertEquals($url, $request->getUri()->getPath());
        }

        if ($depth !== null) {
          $this->validateDepth($request, $depth);
        }

        $this->validateContentType($request);
        $this->validateBody($request, $this->xml_generator->propfindBody($properties));
    }

    protected function validateMkCalendarRequest(Calendar $calendar)
    {
        $request = $this->getAndValidateLastRequest('MKCALENDAR');

        $this->assertEquals($calendar->getUrl(), $request->getUri()->getPath());
        $this->validateContentType($request);
        $this->validateBody(
          $request,
          $this->xml_generator->mkCalendarBody($calendar->getWritableProperties())
        );
    }

    protected function validateProppatchRequest(Calendar $calendar)
    {
        $request = $this->getAndValidateLastRequest('PROPPATCH');

        $this->assertEquals($calendar->getUrl(), $request->getUri()->getPath());
        $this->validateContentType($request);
        $this->validateBody(
            $request,
            $this->xml_generator->proppatchBody($calendar->getWritableProperties())
        );
    }

    protected function validateDeleteCalendarRequest(Calendar $calendar)
    {
        $request = $this->getAndValidateLastRequest('DELETE');
        $this->assertEquals($calendar->getUrl(), $request->getUri()->getPath());
    }

    protected function validateFetchObjectsRequest(Calendar $calendar, ComponentFilter $filter)
    {
        $request = $this->getAndValidateLastRequest('REPORT');

        $this->assertEquals($calendar->getUrl(), $request->getUri()->getPath());
        $this->validateContentType($request);
        $this->validateDepth($request, 1);

        $this->validateBody($request, $this->xml_generator->calendarQueryBody($filter));
    }

    protected function validatePutObjectRequest(CalendarObject $object)
    {
        $request = $this->getAndValidateLastRequest('PUT');

        $this->assertEquals($object->getUrl(), $request->getUri()->getPath());
        $this->validateContentType($request, 'text/calendar');

        if ($object->getEtag() === null) {
            $this->assertEquals(
                '*',
                $request->getHeaderLine('If-None-Match')
            );
        } else {
            $this->assertEquals(
                $object->getEtag(),
                $request->getHeaderLine('If-Match')
            );
        }

        $this->validateBody($request, $object->getRenderedEvent());
    }

    protected function validateDeleteObjectRequest(CalendarObject $object)
    {
        $request = $this->getAndValidateLastRequest('DELETE');

        if ($object->getEtag() !== null) {
            $this->assertEquals(
                $object->getEtag(),
                $request->getHeaderLine('If-Match')
            );
        }
        $this->assertEquals($object->getUrl(), $request->getUri()->getPath());
    }

    protected function validateACLRequest(Calendar $calendar, ACL $acl)
    {
        $request = $this->getAndValidateLastRequest('ACL');

        $this->assertEquals($calendar->getUrl(), $request->getUri()->getPath());

        $this->validateBody($request, $this->xml_generator->aclBody($acl));
    }

    /**
     * Returns last request
     *
     * @string $method HTTP method
     * @return \Psr\Http\Message\RequestInterface
     */
    private function getAndValidateLastRequest($method)
    {
        $this->assertCount(1, $this->history);
        $transaction = end($this->history);
        $request = $transaction['request'];

        $this->assertEquals(
          $method,
          $request->getMethod(),
          'Expected request method to be ' . $method . ', found ' . $request->getMethod()
        );

        return $request;
    }

    /**
     * Validates Content-Type header
     */
    private function validateContentType(RequestInterface $request, $match = 'application/xml; charset=utf-8')
    {
        $this->assertEquals(
            $match,
            $request->getHeaderLine('Content-Type'),
            'Expected Content-Type to be '.$match.', was ' . $request->getHeaderLine('Content-Type')
        );
    }

    /**
     * Validates Depth header
     */
    private function validateDepth(RequestInterface $request, $depth)
    {
          $this->assertEquals(
            $depth,
            (int)$request->getHeaderLine('Depth'),
            'Expected Depth to be ' . $depth . ', is ' . $request->getHeaderLine('Depth')
          );
    }

    /**
     * Validates request body
     */
    private function validateBody(RequestInterface $request, $body)
    {
        $this->assertEquals(
            $body,
            (string)$request->getBody(),
            'Body for request does not match'
        );
    }

}
