<?php

namespace AgenDAV\Controller\Event;

use AgenDAV\AutologinState;
use AgenDAV\CalDAV\Resource\Calendar;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class ReadOnlyCalendarTest extends TestCase
{
    private function makeCalendar(string $url, bool $writable): Calendar
    {
        $calendar = new Calendar($url);
        $calendar->setWritable($writable);
        return $calendar;
    }

    private function makeContainer(array $calendarsByUrl, ?callable $configureClient = null): ContainerInterface
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->method('getCalendarByUrl')
            ->willReturnCallback(fn ($url) => $calendarsByUrl[$url]);

        if ($configureClient !== null) {
            $configureClient($client);
        }

        $translator = $this->createMock(\Symfony\Component\Translation\Translator::class);
        $translator->method('trans')
            ->willReturn('Calendar is read-only');

        $autologinState = $this->createMock(AutologinState::class);
        $autologinState->method('isReadOnly')
            ->willReturn(false);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['caldav.client', $client],
                ['translator', $translator],
                [AutologinState::class, $autologinState],
            ]);

        return $container;
    }

    private function postRequest(array $body): \Psr\Http\Message\ServerRequestInterface
    {
        return (new ServerRequestFactory())
            ->createServerRequest('POST', '/event/test')
            ->withParsedBody($body);
    }

    private function emptyResponse(): \Psr\Http\Message\ResponseInterface
    {
        return (new ResponseFactory())->createResponse();
    }

    public function testDeleteRejectsReadOnlyCalendar()
    {
        $calendar = $this->makeCalendar('/cal/', false);
        $container = $this->makeContainer(['/cal/' => $calendar]);

        $controller = new Delete($container);
        $response = $controller->__invoke(
            $this->postRequest(['calendar' => '/cal/', 'uid' => 'uid1', 'href' => '/cal/uid1.ics', 'etag' => '"abc"']),
            $this->emptyResponse()
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSaveCreateRejectsReadOnlyCalendar()
    {
        $calendar = $this->makeCalendar('/cal/', false);
        $container = $this->makeContainer(['/cal/' => $calendar]);

        $controller = new Save($container);
        $response = $controller->__invoke(
            $this->postRequest([
                'calendar' => '/cal/',
                'summary' => 'Test',
                'timezone' => 'UTC',
                'start' => '2026-06-01T10:00:00.000000Z',
                'end' => '2026-06-01T11:00:00.000000Z',
            ]),
            $this->emptyResponse()
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSaveModifyRejectsReadOnlySourceCalendar()
    {
        $readonly = $this->makeCalendar('/readonly/', false);
        $writable = $this->makeCalendar('/writable/', true);
        $container = $this->makeContainer(['/readonly/' => $readonly, '/writable/' => $writable]);

        $controller = new Save($container);
        $response = $controller->__invoke(
            $this->postRequest([
                'calendar' => '/writable/',
                'original_calendar' => '/readonly/',
                'uid' => 'uid1',
                'etag' => '"abc"',
                'summary' => 'Test',
                'timezone' => 'UTC',
                'start' => '2026-06-01T10:00:00.000000Z',
                'end' => '2026-06-01T11:00:00.000000Z',
            ]),
            $this->emptyResponse()
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSaveModifyRejectsReadOnlyDestinationCalendar()
    {
        $writable = $this->makeCalendar('/writable/', true);
        $readonly = $this->makeCalendar('/readonly/', false);
        $container = $this->makeContainer(['/writable/' => $writable, '/readonly/' => $readonly]);

        $controller = new Save($container);
        $response = $controller->__invoke(
            $this->postRequest([
                'calendar' => '/readonly/',
                'original_calendar' => '/writable/',
                'uid' => 'uid1',
                'etag' => '"abc"',
                'summary' => 'Test',
                'timezone' => 'UTC',
                'start' => '2026-06-01T10:00:00.000000Z',
                'end' => '2026-06-01T11:00:00.000000Z',
            ]),
            $this->emptyResponse()
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDropRejectsReadOnlyCalendar()
    {
        $calendar = $this->makeCalendar('/cal/', false);
        $container = $this->makeContainer(['/cal/' => $calendar]);

        $controller = new Drop($container);
        $response = $controller->__invoke(
            $this->postRequest(['calendar' => '/cal/', 'timezone' => 'UTC', 'uid' => 'uid1', 'delta' => '0', 'was_allday' => 'false', 'allday' => 'false']),
            $this->emptyResponse()
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testResizeRejectsReadOnlyCalendar()
    {
        $calendar = $this->makeCalendar('/cal/', false);
        $container = $this->makeContainer(['/cal/' => $calendar]);

        $controller = new Resize($container);
        $response = $controller->__invoke(
            $this->postRequest(['calendar' => '/cal/', 'timezone' => 'UTC', 'uid' => 'uid1', 'delta' => '30']),
            $this->emptyResponse()
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeleteSucceedsOnWritableCalendar()
    {
        $calendar = $this->makeCalendar('/cal/', true);
        $object = new \AgenDAV\CalDAV\Resource\CalendarObject('/cal/uid1.ics');

        $container = $this->makeContainer(['/cal/' => $calendar], function ($client) use ($object) {
            $client->method('fetchObjectByUid')->willReturn($object);
            $client->method('deleteCalendarObject')->willReturn(null);
        });

        $controller = new Delete($container);
        $response = $controller->__invoke(
            $this->postRequest(['calendar' => '/cal/', 'uid' => 'uid1', 'href' => '/cal/uid1.ics', 'etag' => '"abc"']),
            $this->emptyResponse()
        );

        $this->assertSame(200, $response->getStatusCode());
    }
}
