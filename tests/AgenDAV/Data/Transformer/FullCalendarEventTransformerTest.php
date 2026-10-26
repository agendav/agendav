<?php

namespace AgenDAV\Data\Transformer;

use AgenDAV\Event\FullCalendarEvent;
use AgenDAV\EventInstance;
use PHPUnit\Framework\TestCase;

class FullCalendarEventTransformerTest extends TestCase
{
    /**
     * @dataProvider colors
     */
    public function testTransformColor($color, $expected)
    {
        $event = $this->createMock(EventInstance::class);
        $event->method('getStart')->willReturn(new \DateTimeImmutable('2026-10-26'));
        $event->method('getEnd')->willReturn(new \DateTimeImmutable('2026-10-27'));
        $event->method('isAllDay')->willReturn(true);
        $event->method('getReminders')->willReturn([]);
        $event->method('getColor')->willReturn($color);

        $transformer = new FullCalendarEventTransformer(new \DateTimeZone('UTC'));
        $result = $transformer->transform(new FullCalendarEvent('/e.ics', 'etag', '/cal/', $event));

        $this->assertSame($expected, $result['color']);
    }

    public static function colors()
    {
        return [
            'none' => ['', ''],
            'name' => ['limegreen', '#32cd32'],
            'hex' => ['#FF0000', '#FF0000'],
        ];
    }
}
