<?php

namespace AgenDAV\Controller\Event;

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\Event\VObjectEvent;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sabre\VObject\Reader;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class AlterColorFormatTest extends TestCase
{
    /**
     * @dataProvider formats
     */
    public function testResizeAppliesEventColorFormat(string $format, string $expected)
    {
        $calendar = new Calendar('/cal/');
        $calendar->setWritable(true);

        $vcalendar = Reader::read(
            "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//EN\r\n" .
            "BEGIN:VEVENT\r\nUID:uid1\r\nDTSTAMP:20260601T090000Z\r\n" .
            "DTSTART:20260601T100000Z\r\nDTEND:20260601T110000Z\r\n" .
            "SUMMARY:Test\r\nCOLOR:#FF6347\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"
        );
        $object = new CalendarObject('/cal/uid1.ics', new VObjectEvent($vcalendar));

        $client = $this->createMock(\AgenDAV\CalDAV\Client::class);
        $client->method('getCalendarByUrl')->willReturn($calendar);
        $client->method('fetchObjectByUid')->willReturn($object);
        $client->expects($this->once())
            ->method('uploadCalendarObject')
            ->willReturnCallback(function (CalendarObject $uploaded) use ($expected) {
                $this->assertSame($expected, $uploaded->getEvent()->getEventInstance()->getColor());
                return new Response(204, ['ETag' => '"new"']);
            });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            ['caldav.client', $client],
            ['calendar.read_only', false],
            ['calendar.event_color_format', $format],
        ]);

        $response = (new Resize($container))->__invoke(
            (new ServerRequestFactory())
                ->createServerRequest('POST', '/event/resize')
                ->withParsedBody(['calendar' => '/cal/', 'timezone' => 'UTC', 'uid' => 'uid1', 'delta' => '30']),
            (new ResponseFactory())->createResponse()
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public static function formats()
    {
        return [
            'hex' => ['hex', '#FF6347'],
            'css3' => ['css3', 'tomato'],
        ];
    }
}
