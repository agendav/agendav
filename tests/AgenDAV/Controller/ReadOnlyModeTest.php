<?php

namespace AgenDAV\Controller;

use AgenDAV\Controller\Calendars;
use AgenDAV\Controller\Event;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * With calendar.read_only enabled, every write endpoint must refuse the
 * request before touching the CalDAV server.
 */
class ReadOnlyModeTest extends TestCase
{
    public static function writeControllers(): array
    {
        return [
            'calendar create' => [Calendars\Create::class],
            'calendar save' => [Calendars\Save::class],
            'calendar delete' => [Calendars\Delete::class],
            'event save' => [Event\Save::class],
            'event delete' => [Event\Delete::class],
            'event drop' => [Event\Drop::class],
            'event resize' => [Event\Resize::class],
        ];
    }

    /**
     * @dataProvider writeControllers
     */
    public function testWriteControllerIsDenied(string $controllerClass)
    {
        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->expects($this->never())->method($this->anything());

        $translator = $this->createMock(\Symfony\Component\Translation\Translator::class);
        $translator->method('trans')->willReturn('Denied');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['caldav.client', $client],
                ['translator', $translator],
                ['calendar.read_only', true],
            ]);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/test')
            ->withParsedBody(['calendar' => '/cal/', 'uid' => 'uid1']);
        $response = (new ResponseFactory())->createResponse();

        $controller = new $controllerClass($container);
        $response = $controller->__invoke($request, $response);

        $this->assertSame(403, $response->getStatusCode());
    }
}
