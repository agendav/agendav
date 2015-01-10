<?php

namespace AgenDAV\Event;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Mockery as m;

class VObjectEventTest extends \PHPUnit_Framework_TestCase
{
    protected $vcalendar;

    protected $vevent;

    public static $rrule = 'FREQ=DAILY;COUNT=4';

    public function setUp()
    {
        $this->vcalendar = new VCalendar;
        $this->vevent = $this->vcalendar->add('VEVENT', [
            'UID' => '123456',
        ]);
    }

    public function testDefaultConstruct()
    {
        $event = new VObjectEvent($this->vcalendar);

        $this->assertFalse($event->isRecurrent());
        $this->assertEquals('123456', $event->getUid());
    }

    /** @expectedException \LogicException */
    public function testSetUidWhenAlreadySet()
    {
        $event = new VObjectEvent($this->vcalendar);
        $event->setUid('9876');
    }

    public function testSetUidOnEmptyVCalendar()
    {
        unset($this->vcalendar->VEVENT);
        $event = new VObjectEvent($this->vcalendar);
        $event->setUid('9876');
        $this->assertEquals('9876', $event->getUid());
    }

    public function testGetRepeatRule()
    {
        $this->vevent->RRULE = self::$rrule;
        $event = new VObjectEvent($this->vcalendar);

        $this->assertTrue($event->isRecurrent());
        $this->assertEquals(self::$rrule, $event->getRepeatRule());
    }

    public function testExpand()
    {
        $now = new \DateTime;
        $this->vevent->DTSTART = $now;
        $this->vevent->RRULE = self::$rrule;
        $event = new VObjectEvent($this->vcalendar);

        // Use a period that contains all instances
        $start = clone $now;
        $start->modify('-1 day');
        $end = clone $now;
        $end->modify('+10 days');

        $instances = $event->expand($start, $end);

        $this->assertContainsOnlyInstancesOf(
            '\AgenDAV\Event\VObjectEventInstance',
            $instances
        );
        // COUNT=4
        $this->assertCount(4, $instances);

        // Make sure all properties match
        foreach ($instances as $instance) {
            $this->assertEquals($event->getUid(), $instance->getUid());
            $this->assertEquals(self::$rrule, $instance->getRepeatRule());
            $this->assertNotNull($instance->getRecurrenceId());
        }
    }

    public function testIsException()
    {
        $this->vevent->RRULE = self::$rrule;
        $this->vcalendar->add('VEVENT', [
            'RECURRENCE-ID' => '20150110T092100Z',
        ]);

        $event = new VObjectEvent($this->vcalendar);
        $this->assertTrue($event->isException('20150110T092100Z'));
    }

    /** @expectedException \LogicException */
    public function testCreateEventInstanceWithNoUid()
    {
        unset($this->vevent->UID);
        $event = new VObjectEvent($this->vcalendar);

        $instance = $event->createEventInstance();
    }

    public function testCreateEventInstanceFromEmptyEvent()
    {
        unset($this->vcalendar->VEVENT);
        $event = new VObjectEvent($this->vcalendar);
        $event->setUid('9876');

        $instance = $event->createEventInstance();
        $this->assertInstanceOf('\AgenDAV\Event\VObjectEventInstance', $instance);
        $this->assertEquals('9876', $instance->getUid());
    }

    public function testCreateEventInstance()
    {
        $this->vevent->RRULE = self::$rrule;
        $event = new VObjectEvent($this->vcalendar);

        $instance = $event->createEventInstance();
        $this->assertInstanceOf('\AgenDAV\Event\VObjectEventInstance', $instance);
        $this->assertEquals('123456', $instance->getUid());
        $this->assertTrue($instance->isRecurrent());
        $this->assertEquals(self::$rrule, $instance->getRepeatRule());
    }

    /** @expectedException \InvalidArgumentException */
    public function testSetBaseInstanceNoMatchingIds()
    {
        $event = new VObjectEvent($this->vcalendar);

        $another_vevent = $this->vcalendar->add('VEVENT', [
            'UID' => 'xxxx',
        ]);;

        $instance = new VObjectEventInstance($another_vevent);
        $event->setBaseEventInstance($instance);
    }

    /** @expectedException \Exception */
    public function testSetBaseInstanceException()
    {
        $vevent_exception = $this->vcalendar->add('VEVENT', [
            'RECURRENCE-ID' => '20150110T092100Z',
        ]);
        $event = new VObjectEvent($this->vcalendar);

        $instance = new VObjectEventInstance($vevent_exception);
        $event->setBaseEventInstance($instance);
    }

    public function testSetBaseInstanceEmptyEvent()
    {
        unset($this->vcalendar->VEVENT);
        $event = new VObjectEvent($this->vcalendar);
        $event->setUid('9876');

        $instance = $event->createEventInstance();
        $instance->setStart(new \DateTime());
        $instance->setRecurrenceId('20150110T100500Z');

        $event->setBaseEventInstance($instance);

        $this->assertEmpty($instance->getRecurrenceId());
    }

    public function testSetBaseInstanceWithBase()
    {
        // Set this on the base event instance
        // to check if it is already there after
        // merging with the new one
        $this->vevent->MYPROPERTY = 'Mark';
        $event = new VObjectEvent($this->vcalendar);

        $instance = $event->createEventInstance();
        $now = new \DateTime();
        $instance->setStart(new \DateTime(), true);
        $instance->setSummary('New test summary');
        $instance->setRecurrenceId('20150110T100500Z');

        $event->setBaseEventInstance($instance);

        // Check directly on the VCALENDAR object
        $vevent = $this->vcalendar->getBaseComponent();
        $this->assertEmpty($vevent->{'RECURRENCE-ID'});
        $this->assertEquals('Mark', $vevent->MYPROPERTY);
        $this->assertEquals('New test summary', $vevent->SUMMARY);
    }
}
