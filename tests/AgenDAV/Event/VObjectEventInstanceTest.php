<?php

namespace AgenDAV\Event;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * @author jorge
 */
class VObjectEventInstanceTest extends \PHPUnit_Framework_TestCase
{
    protected $vcalendar;

    public function setUp()
    {
        $this->vcalendar = new VCalendar;
    }

    public function testIsRecurrent()
    {
        $vevent_repeat = $this->vcalendar->add('VEVENT', [
            'RRULE' => 'FREQ=DAILY',
        ]);

        $vevent_no_repeat = $this->vcalendar->add('VEVENT', [
        ]);

        $instance_repeat = new VObjectEventInstance($vevent_repeat);
        $instance_no_repeat = new VObjectEventInstance($vevent_no_repeat);

        $this->assertTrue($instance_repeat->isRecurrent());
        $this->assertFalse($instance_no_repeat->isRecurrent());
    }

    public function testSomeGets()
    {
        $vevent = $this->vcalendar->add('VEVENT', [
            'UID' => '12345',
            'SUMMARY' => 'Test summary',
            'LOCATION' => 'Test location',
            'DESCRIPTION' => 'Test description',
            'CLASS' => 'PUBLIC',
            'TRANSP' => 'OPAQUE',
        ]);

        $instance = new VObjectEventInstance($vevent);

        $this->assertEquals($instance->getUid(), '12345');
        $this->assertEquals($instance->getSummary(), 'Test summary');
        $this->assertEquals($instance->getLocation(),  'Test location');
        $this->assertEquals($instance->getDescription(), 'Test description');
        $this->assertEquals($instance->getClass(), 'PUBLIC');
        $this->assertEquals($instance->getTransp(), 'OPAQUE');
    }
}
