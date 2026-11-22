<?php

namespace AgenDAV\Controller;

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Controller\Calendars;
use AgenDAV\Controller\Event;
use AgenDAV\Data\Principal;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Checks status codes and response formats of the JSON API
 * (see docs/source/development/api.rst).
 */
class ApiTest extends TestCase
{
    public static function controllersWithRequiredInput(): array
    {
        return [
            'calendar create' => [Calendars\Create::class, 'POST'],
            'calendar save' => [Calendars\Save::class, 'POST'],
            'calendar delete' => [Calendars\Delete::class, 'POST'],
            'event listing' => [Event\Listing::class, 'GET'],
            'event base' => [Event\GetBase::class, 'GET'],
            'event save' => [Event\Save::class, 'POST'],
            'event delete' => [Event\Delete::class, 'POST'],
            'event drop' => [Event\Drop::class, 'POST'],
            'event resize' => [Event\Resize::class, 'POST'],
        ];
    }

    /**
     * @dataProvider controllersWithRequiredInput
     */
    public function testMissingInputReturnsExceptionEnvelope(string $controllerClass, string $method)
    {
        $container = $this->makeContainer($this->createMock(\AgenDAV\CalDAV\Client::class));
        $controller = new $controllerClass($container);

        $response = $controller->__invoke($this->makeRequest($method, []), $this->makeResponse());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(['result' => 'EXCEPTION', 'message' => 'Translated'], $this->decode($response));
    }

    public static function requiredFields(): array
    {
        $cases = [];
        $controllers = [
            'calendar create' => [Calendars\Create::class, 'POST', [
                'displayname' => 'Work',
                'calendar_color' => '#3a89c9',
            ]],
            'calendar save' => [Calendars\Save::class, 'POST', [
                'calendar' => '/cal/',
                'displayname' => 'Work',
                'calendar_color' => '#3a89c9',
            ]],
            'calendar delete' => [Calendars\Delete::class, 'POST', [
                'calendar' => '/cal/',
            ]],
            'event listing' => [Event\Listing::class, 'GET', [
                'calendar' => '/cal/',
                'timezone' => 'UTC',
                'start' => '2026-11-01',
                'end' => '2026-12-01',
            ]],
            'event base' => [Event\GetBase::class, 'GET', [
                'calendar' => '/cal/',
                'uid' => 'uid1',
                'timezone' => 'UTC',
            ]],
            'event create' => [Event\Save::class, 'POST', [
                'calendar' => '/cal/',
                'summary' => 'Meeting',
                'timezone' => 'UTC',
                'start' => '2026-11-23T10:00:00.000Z',
                'end' => '2026-11-23T11:00:00.000Z',
            ]],
            'event modify' => [Event\Save::class, 'POST', [
                'uid' => 'uid1',
                'calendar' => '/cal/',
                'summary' => 'Meeting',
                'timezone' => 'UTC',
                'start' => '2026-11-23T10:00:00.000Z',
                'end' => '2026-11-23T11:00:00.000Z',
                'etag' => '"e1"',
                'original_calendar' => '/cal/',
            ]],
            'event delete' => [Event\Delete::class, 'POST', [
                'calendar' => '/cal/',
                'uid' => 'uid1',
                'href' => '/cal/uid1.ics',
                'etag' => '"e1"',
            ]],
            'event drop' => [Event\Drop::class, 'POST', [
                'calendar' => '/cal/',
                'timezone' => 'UTC',
                'uid' => 'uid1',
                'delta' => '30',
                'was_allday' => 'false',
                'allday' => 'false',
            ]],
            'event resize' => [Event\Resize::class, 'POST', [
                'calendar' => '/cal/',
                'timezone' => 'UTC',
                'uid' => 'uid1',
                'delta' => '30',
            ]],
        ];

        foreach ($controllers as $name => [$controllerClass, $method, $input]) {
            foreach (array_keys($input) as $field) {
                if ($name === 'event modify' && $field === 'uid') {
                    continue; // without uid it is a valid create request
                }
                $cases[$name . ' without ' . $field] = [$controllerClass, $method, $input, $field];
            }
        }

        return $cases;
    }

    /**
     * @dataProvider requiredFields
     */
    public function testEachRequiredFieldIsValidated(string $controllerClass, string $method, array $input, string $field)
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->expects($this->never())->method($this->anything());
        $controller = new $controllerClass($this->makeContainer($client));

        unset($input[$field]);
        $response = $controller->__invoke($this->makeRequest($method, $input), $this->makeResponse());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('EXCEPTION', $this->decode($response)['result']);
    }

    public function testEventSaveRejectsEndBeforeStart()
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->expects($this->never())->method($this->anything());

        $response = (new Event\Save($this->makeContainer($client)))->__invoke(
            $this->makeRequest('POST', [
                'calendar' => '/cal/',
                'summary' => 'Meeting',
                'timezone' => 'UTC',
                'start' => '2026-11-23T11:00:00.000Z',
                'end' => '2026-11-23T10:00:00.000Z',
            ]),
            $this->makeResponse()
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testEventCreateReturnsChangedCalendar()
    {
        $client = $this->makeEventClient(['/cal/' => $this->makeWritableCalendar('/cal/')]);
        $client->expects($this->once())->method('uploadCalendarObject');

        $response = (new Event\Save($this->makeContainer($client)))->__invoke(
            $this->makeRequest('POST', [
                'calendar' => '/cal/',
                'summary' => 'Meeting',
                'timezone' => 'UTC',
                'start' => '2026-11-23T10:00:00.000Z',
                'end' => '2026-11-23T11:00:00.000Z',
                'allday' => 'false',
            ]),
            $this->makeResponse()
        );

        $this->assertSame(['result' => 'SUCCESS', 'message' => ['/cal/']], $this->decode($response));
    }

    public static function allDayValues(): array
    {
        return [
            'string true' => ['true', true],
            'string false' => ['false', false],
            'JSON true' => [true, true],
            'JSON false' => [false, false],
            'missing' => [null, false],
        ];
    }

    /**
     * @dataProvider allDayValues
     */
    public function testEventCreateAcceptsAllDayValues(string|bool|null $allDay, bool $expected)
    {
        $uploaded = null;
        $client = $this->makeEventClient(['/cal/' => $this->makeWritableCalendar('/cal/')], $uploaded);

        $input = [
            'calendar' => '/cal/',
            'summary' => 'Meeting',
            'timezone' => 'UTC',
            'start' => '2026-11-23T10:00:00.000Z',
            'end' => '2026-11-23T11:00:00.000Z',
        ];
        if ($allDay !== null) {
            $input['allday'] = $allDay;
        }
        (new Event\Save($this->makeContainer($client)))->__invoke(
            $this->makeRequest('POST', $input),
            $this->makeResponse()
        );

        $this->assertSame($expected, $uploaded->getEvent()->getEventInstance()->isAllDay());
    }

    public function testEventModifyReturnsChangedCalendar()
    {
        $client = $this->makeEventClient(['/cal/' => $this->makeWritableCalendar('/cal/')]);
        $client->method('fetchObjectByUid')->willReturn($this->makeEventObject('/cal/'));
        $client->expects($this->never())->method('deleteCalendarObject');

        $response = (new Event\Save($this->makeContainer($client)))->__invoke(
            $this->makeRequest('POST', $this->modifyInput('/cal/', '/cal/')),
            $this->makeResponse()
        );

        $this->assertSame(['result' => 'SUCCESS', 'message' => ['/cal/']], $this->decode($response));
    }

    public function testEventMoveReturnsBothCalendars()
    {
        $client = $this->makeEventClient([
            '/old/' => $this->makeWritableCalendar('/old/'),
            '/new/' => $this->makeWritableCalendar('/new/'),
        ]);
        $client->method('fetchObjectByUid')->willReturn($this->makeEventObject('/old/'));
        $client->expects($this->once())->method('deleteCalendarObject');

        $response = (new Event\Save($this->makeContainer($client)))->__invoke(
            $this->makeRequest('POST', $this->modifyInput('/old/', '/new/')),
            $this->makeResponse()
        );

        $this->assertSame(['result' => 'SUCCESS', 'message' => ['/old/', '/new/']], $this->decode($response));
    }

    public static function dropMovements(): array
    {
        return [
            'timed to timed' => ['false', 'false', 30, '2026-11-23 10:30', '2026-11-23 11:30', false],
            'timed to all day' => ['false', 'true', 0, '2026-11-23 00:00', '2026-11-24 00:00', true],
            'JSON booleans' => [false, true, 0, '2026-11-23 00:00', '2026-11-24 00:00', true],
        ];
    }

    /**
     * Booleans are accepted as strings ("true"/"false") and as JSON booleans.
     *
     * @dataProvider dropMovements
     */
    public function testEventDropMovesEventAndReturnsEtag(
        string|bool $wasAllDay,
        string|bool $allDay,
        int $delta,
        string $expectedStart,
        string $expectedEnd,
        bool $expectedAllDay
    ) {
        $uploaded = null;
        $client = $this->makeEventClient(['/cal/' => $this->makeWritableCalendar('/cal/')], $uploaded);
        $client->method('fetchObjectByUid')->willReturn($this->makeEventObject('/cal/'));

        $response = (new Event\Drop($this->makeContainer($client)))->__invoke(
            $this->makeRequest('POST', [
                'calendar' => '/cal/',
                'timezone' => 'UTC',
                'uid' => 'uid1',
                'delta' => $delta,
                'was_allday' => $wasAllDay,
                'allday' => $allDay,
            ]),
            $this->makeResponse()
        );

        $this->assertSame(['result' => 'SUCCESS', 'message' => ['etag' => '"e2"']], $this->decode($response));
        $instance = $uploaded->getEvent()->getEventInstance();
        $this->assertSame($expectedStart, $instance->getStart()->format('Y-m-d H:i'));
        $this->assertSame($expectedEnd, $instance->getEnd()->format('Y-m-d H:i'));
        $this->assertSame($expectedAllDay, $instance->isAllDay());
    }

    public function testEventResizeChangesEndAndReturnsEtag()
    {
        $uploaded = null;
        $client = $this->makeEventClient(['/cal/' => $this->makeWritableCalendar('/cal/')], $uploaded);
        $client->method('fetchObjectByUid')->willReturn($this->makeEventObject('/cal/'));

        $response = (new Event\Resize($this->makeContainer($client)))->__invoke(
            $this->makeRequest('POST', [
                'calendar' => '/cal/',
                'timezone' => 'UTC',
                'uid' => 'uid1',
                'delta' => 30,
            ]),
            $this->makeResponse()
        );

        $this->assertSame(['result' => 'SUCCESS', 'message' => ['etag' => '"e2"']], $this->decode($response));
        $instance = $uploaded->getEvent()->getEventInstance();
        $this->assertSame('2026-11-23 10:00', $instance->getStart()->format('Y-m-d H:i'));
        $this->assertSame('2026-11-23 11:30', $instance->getEnd()->format('Y-m-d H:i'));
    }

    public function testEventDeleteReturnsEmptyMessage()
    {
        $client = $this->makeEventClient(['/cal/' => $this->makeWritableCalendar('/cal/')]);
        $client->method('fetchObjectByUid')->willReturn($this->makeEventObject('/cal/'));
        $client->expects($this->once())->method('deleteCalendarObject');

        $response = (new Event\Delete($this->makeContainer($client)))->__invoke(
            $this->makeRequest('POST', [
                'calendar' => '/cal/',
                'uid' => 'uid1',
                'href' => '/cal/uid1.ics',
                'etag' => '"e1"',
            ]),
            $this->makeResponse()
        );

        $this->assertSame(['result' => 'SUCCESS', 'message' => ''], $this->decode($response));
    }

    public function testEventDeleteInstanceReturnsEtag()
    {
        $client = $this->makeEventClient(['/cal/' => $this->makeWritableCalendar('/cal/')]);
        $client->method('fetchObjectByUid')->willReturn($this->makeEventObject('/cal/', 'FREQ=DAILY'));
        $client->expects($this->never())->method('deleteCalendarObject');
        $client->expects($this->once())->method('uploadCalendarObject');

        $response = (new Event\Delete($this->makeContainer($client)))->__invoke(
            $this->makeRequest('POST', [
                'calendar' => '/cal/',
                'uid' => 'uid1',
                'href' => '/cal/uid1.ics',
                'etag' => '"e1"',
                'recurrence_id' => '20261124T100000Z',
            ]),
            $this->makeResponse()
        );

        $this->assertSame(['result' => 'SUCCESS', 'message' => ['etag' => '"e2"']], $this->decode($response));
    }

    public function testEventBaseReturnsSingleEventObject()
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->method('fetchObjectByUid')->willReturn($this->makeEventObject('/cal/'));

        $response = (new Event\GetBase($this->makeContainer($client)))->__invoke(
            $this->makeRequest('GET', ['calendar' => '/cal/', 'uid' => 'uid1', 'timezone' => 'Europe/Berlin']),
            $this->makeResponse()
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame('/cal/uid1', $body['id']);
        $this->assertSame('uid1', $body['uid']);
        $this->assertSame('Meeting', $body['title']);
        $this->assertSame('2026-11-23T11:00:00+01:00', $body['start']);
        $this->assertFalse($body['allDay']);
    }

    public static function exceptionMapping(): array
    {
        return [
            'permission denied' => [new \AgenDAV\Exception\PermissionDenied('denied'), 403, 'EXCEPTION'],
            'not found' => [new \AgenDAV\Exception\NotFound('missing'), 404, 'EXCEPTION'],
            'element modified' => [new \AgenDAV\Exception\ElementModified('changed'), 412, 'EXCEPTION'],
            'connection problem' => [new \AgenDAV\Exception\ConnectionProblem('down'), 503, 'ERROR'],
            'unexpected CalDAV status' => [new \AgenDAV\Exception('teapot'), 500, 'ERROR'],
            'internal error' => [new \RuntimeException('boom'), 500, 'ERROR'],
        ];
    }

    /**
     * @dataProvider exceptionMapping
     */
    public function testExceptionsAreMappedToEnvelope(\Exception $exception, int $status, string $result)
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->method('deleteCalendar')->willThrowException($exception);

        $controller = new Calendars\Delete($this->makeContainer($client));
        $response = $controller->__invoke(
            $this->makeRequest('POST', ['calendar' => '/cal/']),
            $this->makeResponse()
        );

        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame(['result' => $result, 'message' => 'Translated'], $this->decode($response));
    }

    public function testSuccessReturnsSuccessEnvelope()
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->expects($this->once())->method('deleteCalendar');

        $controller = new Calendars\Delete($this->makeContainer($client));
        $response = $controller->__invoke(
            $this->makeRequest('POST', ['calendar' => '/cal/']),
            $this->makeResponse()
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['result' => 'SUCCESS', 'message' => '/cal/'], $this->decode($response));
    }

    public function testCalendarListingReturnsPlainArray()
    {
        $calendar = new Calendar('/cal/', [Calendar::DISPLAYNAME => 'Default']);
        $calendar->setOwner(new Principal('/principals/test/'));
        $calendar->setWritable(true);

        $finder = $this->createMock(\AgenDAV\CalendarFinder::class);
        $finder->method('getCalendars')->willReturn([$calendar]);

        $container = $this->makeContainer(
            $this->createMock(\AgenDAV\CalDAV\Client::class),
            ['calendar.finder' => $finder]
        );
        $response = (new Calendars\Listing($container))->__invoke(
            $this->makeRequest('GET', []),
            $this->makeResponse()
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame([0], array_keys($body));
        $this->assertSame(
            [
                'url', 'calendar', 'displayname', 'color', 'order', 'ctag', 'is_owned',
                'is_shared', 'is_subscribed', 'owner', 'writable', 'shares',
            ],
            array_keys($body[0])
        );
        $this->assertSame('/cal/', $body[0]['url']);
        $this->assertTrue($body[0]['is_owned']);
        $this->assertTrue($body[0]['writable']);
    }

    public function testEventListingReturnsPlainArray()
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->method('fetchObjectsOnCalendar')->willReturn([]);

        $response = (new Event\Listing($this->makeContainer($client)))->__invoke(
            $this->makeRequest('GET', [
                'calendar' => '/cal/',
                'timezone' => 'Europe/Berlin',
                'start' => '2026-11-01',
                'end' => '2026-12-01',
            ]),
            $this->makeResponse()
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $this->decode($response));
        $this->assertNotSame('', $response->getHeaderLine('X-Fetch-Time'));
        $this->assertNotSame('', $response->getHeaderLine('X-Parse-Time'));
    }

    public function testEventListingDeniedReturnsExceptionEnvelope()
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->method('fetchObjectsOnCalendar')
            ->willThrowException(new \AgenDAV\Exception\PermissionDenied('denied'));

        $response = (new Event\Listing($this->makeContainer($client)))->__invoke(
            $this->makeRequest('GET', [
                'calendar' => '/cal/',
                'timezone' => 'Europe/Berlin',
                'start' => '2026-11-01',
                'end' => '2026-12-01',
            ]),
            $this->makeResponse()
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['result' => 'EXCEPTION', 'message' => 'Translated'], $this->decode($response));
    }

    public function testPrincipalSearchWithoutTermReturnsEmptyArray()
    {
        $container = $this->createMock(ContainerInterface::class);
        $response = (new Principals($container))->search(
            $this->makeRequest('GET', []),
            $this->makeResponse()
        );

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame([], $this->decode($response));
    }

    public function testPrincipalSearchReturnsPlainArray()
    {
        $principal = new Principal('/principals/demo/');
        $principal->setDisplayName('Demo');
        $principal->setEmail('demo@example.com');

        $repository = $this->createMock(\AgenDAV\Repositories\PrincipalsRepository::class);
        $repository->method('search')->with('dem')->willReturn([$principal]);

        $container = $this->makeContainer(
            $this->createMock(\AgenDAV\CalDAV\Client::class),
            ['principals.repository' => $repository]
        );
        $response = (new Principals($container))->search(
            $this->makeRequest('GET', ['term' => 'dem']),
            $this->makeResponse()
        );

        $this->assertSame(
            [['url' => '/principals/demo/', 'displayname' => 'Demo', 'email' => 'demo@example.com']],
            $this->decode($response)
        );
    }

    private function makeWritableCalendar(string $url): Calendar
    {
        $calendar = new Calendar($url);
        $calendar->setWritable(true);
        return $calendar;
    }

    /**
     * CalDAV client mock for event writes. Uploads answer with ETag "e2",
     * the uploaded object is stored in $uploaded.
     */
    private function makeEventClient(array $calendarsByUrl, &$uploaded = null): \AgenDAV\CalDAV\Client
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->method('getCalendarByUrl')->willReturnCallback(fn ($url) => $calendarsByUrl[$url]);
        $client->method('uploadCalendarObject')->willReturnCallback(
            function ($object) use (&$uploaded) {
                $uploaded = $object;
                return new \GuzzleHttp\Psr7\Response(201, ['ETag' => '"e2"']);
            }
        );
        return $client;
    }

    /**
     * Event "Meeting" on 2026-11-23 10:00-11:00 UTC with UID uid1.
     */
    private function makeEventObject(string $calendarUrl, ?string $rrule = null): \AgenDAV\CalDAV\Resource\CalendarObject
    {
        $builder = new \AgenDAV\Event\Builder\VObjectBuilder(new \DateTimeZone('UTC'));
        $event = $builder->createEvent('uid1');
        $input = [
            'summary' => 'Meeting',
            'start' => '2026-11-23T10:00:00.000Z',
            'end' => '2026-11-23T11:00:00.000Z',
            'allday' => 'false',
        ];
        if ($rrule !== null) {
            $input['rrule'] = $rrule;
        }
        $event->storeInstance($builder->createEventInstanceWithInput($event, $input));

        $object = new \AgenDAV\CalDAV\Resource\CalendarObject($calendarUrl . 'uid1.ics', $event);
        $object->setEtag('"e1"');
        return $object;
    }

    private function modifyInput(string $originalCalendar, string $calendar): array
    {
        return [
            'uid' => 'uid1',
            'calendar' => $calendar,
            'original_calendar' => $originalCalendar,
            'summary' => 'Renamed',
            'timezone' => 'UTC',
            'start' => '2026-11-23T10:00:00.000Z',
            'end' => '2026-11-23T11:00:00.000Z',
            'allday' => 'false',
            'etag' => '"e1"',
        ];
    }

    private function makeContainer(\AgenDAV\CalDAV\Client $client, array $services = []): ContainerInterface
    {
        $translator = $this->createMock(\Symfony\Component\Translation\Translator::class);
        $translator->method('trans')->willReturn('Translated');

        $session = $this->createMock(\Symfony\Component\HttpFoundation\Session\SessionInterface::class);
        $session->method('get')->willReturn('/principals/test/');

        $fractal = new \League\Fractal\Manager();
        $fractal->setSerializer(new \League\Fractal\Serializer\DataArraySerializer());

        $services += [
            'caldav.client' => $client,
            'translator' => $translator,
            'calendar.read_only' => false,
            'session' => $session,
            'subscriptions.repository' => $this->createMock(\AgenDAV\Repositories\SubscriptionsRepository::class),
            'monolog' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'fractal' => $fractal,
            'event.builder' => new \AgenDAV\Event\Builder\VObjectBuilder(new \DateTimeZone('UTC')),
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id]);

        return $container;
    }

    private function makeRequest(string $method, array $params): ServerRequestInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, '/test');
        if ($method === 'GET') {
            return $request->withQueryParams($params);
        }
        return $request->withParsedBody($params);
    }

    private function makeResponse(): ResponseInterface
    {
        return (new ResponseFactory())->createResponse();
    }

    private function decode(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true);
    }
}
