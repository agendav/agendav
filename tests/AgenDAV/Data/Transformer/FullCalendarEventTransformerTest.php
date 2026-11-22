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

    /**
     * Zero-duration timed events (DTSTART == DTEND) are valid and must keep
     * identical start and end values (#381)
     */
    public function testTransformZeroDuration()
    {
        $start = new \DateTimeImmutable('2026-10-26 13:00:00', new \DateTimeZone('UTC'));
        $event = $this->createMock(EventInstance::class);
        $event->method('getStart')->willReturn($start);
        $event->method('getEnd')->willReturn($start);
        $event->method('isAllDay')->willReturn(false);
        $event->method('getReminders')->willReturn([]);
        $event->method('getColor')->willReturn('');

        $transformer = new FullCalendarEventTransformer(new \DateTimeZone('UTC'));
        $result = $transformer->transform(new FullCalendarEvent('/e.ics', 'etag', '/cal/', $event));

        $this->assertSame('2026-10-26T13:00:00+00:00', $result['start']);
        $this->assertSame($result['start'], $result['end']);
        $this->assertFalse($result['allDay']);
    }

    public function testTransformStatus()
    {
        $start = new \DateTimeImmutable('2026-10-26 13:00:00', new \DateTimeZone('UTC'));
        $event = $this->createMock(EventInstance::class);
        $event->method('getStart')->willReturn($start);
        $event->method('getEnd')->willReturn($start);
        $event->method('getReminders')->willReturn([]);
        $event->method('getColor')->willReturn('');
        $event->method('getStatus')->willReturn('CANCELLED');

        $transformer = new FullCalendarEventTransformer(new \DateTimeZone('UTC'));
        $result = $transformer->transform(new FullCalendarEvent('/e.ics', 'etag', '/cal/', $event));

        $this->assertSame('CANCELLED', $result['status']);
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
