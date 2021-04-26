<?php

namespace AgenDAV\CalDAV\Resource;

use PHPUnit\Framework\TestCase;

class CalendarObjectTest extends TestCase
{
    public function testGenerateOnCalendar()
    {
        $calendar = new Calendar('/calendar1/');
        $object = CalendarObject::generateOnCalendar($calendar, '123456');

        $this->assertEquals($calendar, $object->getCalendar());

        $expected_url = $calendar->getUrl() . '123456.ics';
        $this->assertEquals($expected_url, $object->getUrl());
    }
}
