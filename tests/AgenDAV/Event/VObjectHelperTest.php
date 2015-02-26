<?php

namespace AgenDAV\Event;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use AgenDAV\Event\VObjectHelper;
use Mockery as m;

class VObjectHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $vcalendar;

    public function setUp()
    {
        $this->vcalendar = new VCalendar;
    }

    public function testSetBaseVEventEmptyVCalendar()
    {
        $vevent = $this->vcalendar->create('VEVENT');
        $vevent->SUMMARY = 'Test event';
        $vevent->DTSTART = new \DateTime();

        VObjectHelper::setBaseVEvent($this->vcalendar, $vevent);

        $this->assertEquals($this->vcalendar->VEVENT, $vevent);
    }

    public function testSetBaseVEventSimpleVCalendar()
    {
        $this->vcalendar->add('VTIMEZONE', []);

        // The base VEVENT will be the second children
        $this->vcalendar->add('VEVENT', [
            'SUMMARY' => 'This vevent will disappear',
        ]);

        $vevent = $this->vcalendar->create('VEVENT');
        $vevent->SUMMARY = 'Test event';
        $vevent->DTSTART = new \DateTime();

        VObjectHelper::setBaseVEvent($this->vcalendar, $vevent);

        $this->assertEquals($this->vcalendar->VEVENT, $vevent);
    }

    public function testSetBaseVEventWithExceptions()
    {
        $this->vcalendar->add('VEVENT', [
            'SUMMARY' => 'This vevent will disappear',
            'DTSTART' => '20150220T184900Z',
            'RRULE' => 'FREQ=DAILY',
        ]);

        $this->vcalendar->add('VEVENT', [
            'SUMMARY' => 'This is an exception',
            'RECURRENCE-ID' => '20150227T184900Z',
        ]);

        $this->vcalendar->add('VEVENT', [
            'SUMMARY' => 'This is an exception',
            'RECURRENCE-ID' => '20150226T184900Z',
        ]);

        $vevent = $this->vcalendar->create('VEVENT');
        $vevent->SUMMARY = 'New base vevent';
        $vevent->DTSTART = '20150220T184900Z';
        $vevent->RRULE = 'FREQ=DAILY';

        VObjectHelper::setBaseVEvent($this->vcalendar, $vevent);

        $this->assertEquals($this->vcalendar->VEVENT, $vevent);
    }
}
