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
        // MYPROPERTY is a sample property used to check if our code copies
        // all unhandled properties to new instances
        $this->vevent = $this->vcalendar->add('VEVENT', [
            'UID' => '123456',
            'SUMMARY' => 'Initial event instance',
            'MYPROPERTY' => 'Check',
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

    public function testIsExceptionWithEmptyRecurrenceId()
    {
        $this->vevent->RRULE = self::$rrule;
        $vevent = $this->vcalendar->add('VEVENT', [
            'RECURRENCE-ID' => '20150413T143500',
        ]);

        $event = new VObjectEvent($this->vcalendar);
        $this->assertFalse($event->isException(''));
        $this->assertFalse($event->isException(null));
    }

    public function testIsExceptionWithTimeZone()
    {
        $this->vevent->RRULE = self::$rrule;
        $vevent = $this->vcalendar->add('VEVENT', [
            'RECURRENCE-ID' => '20150413T143500',
        ]);

        $vevent->{'RECURRENCE-ID'}['TZID'] = 'Europe/Madrid';

        $event = new VObjectEvent($this->vcalendar);
        $this->assertTrue(
            $event->isException('20150413T123500Z'),
            'Detection of non-UTC RECURRENCE-IDs is not working'
        );
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
        $event->storeInstance($instance);
    }

    /** @expectedException \Exception */
    public function testSetBaseInstanceException()
    {
        $vevent_exception = $this->vcalendar->add('VEVENT', [
            'RECURRENCE-ID' => '20150110T092100Z',
        ]);
        $event = new VObjectEvent($this->vcalendar);

        $instance = new VObjectEventInstance($vevent_exception);
        $event->storeInstance($instance);
    }

    public function testSetBaseInstanceEmptyEvent()
    {
        unset($this->vcalendar->VEVENT);
        $event = new VObjectEvent($this->vcalendar);
        $event->setUid('9876');

        $instance = $event->createEventInstance();
        $instance->setStart(new \DateTime());
        $instance->setRecurrenceId('20150110T100500Z');

        $event->storeInstance($instance);

        $this->assertEmpty($instance->getRecurrenceId());
    }

    public function testSetBaseInstanceWithBase()
    {
        $event = new VObjectEvent($this->vcalendar);

        $instance = $event->createEventInstance();
        $now = new \DateTime();
        $instance->setStart(new \DateTime(), true);
        $instance->setSummary('New test summary');
        $instance->setRecurrenceId('20150110T100500Z');

        $event->storeInstance($instance);

        // Check directly on the VCALENDAR object
        $vevent = $this->vcalendar->getBaseComponent();
        $this->assertEmpty($vevent->{'RECURRENCE-ID'});
        $this->assertEquals('Check', $vevent->MYPROPERTY);
        $this->assertEquals('New test summary', $vevent->SUMMARY);
    }


    /** @expectedException \Exception */
    public function testStoreInstanceModifyBaseWithExceptions()
    {
        $vevent_exception = $this->generateRecurrentEvent();
        $event = new VObjectEvent($this->vcalendar);

        // Get base instance
        $instance = $event->getEventInstance();
        $instance->setSummary('I am trying to modify the base instance');

        $event->storeInstance($instance);
    }

    public function testStoreInstanceNewException()
    {
        $this->generateRecurrentEvent();
        $event = new VObjectEvent($this->vcalendar);

        $recurrence_id = '20150128T012345Z';

        $new_exception = $event->getEventInstance($recurrence_id);
        $new_exception->setSummary('I am a new exception');
        $new_exception->setStart(new \DateTime);

        $event->storeInstance($new_exception);

        $this->assertTrue($event->isException($recurrence_id));

        // Check that base VEVENT is still there
        $base = $event->getEventInstance();
        $this->assertEquals(
           $this->vevent->SUMMARY,
           $base->getSummary()
       );

        // Some checks on new instance
        $this->assertTrue($event->isException($recurrence_id));
        $retrieved_exception = $event->getEventInstance($recurrence_id);
        $this->assertEquals(
            'Check',
            $retrieved_exception->getInternalVEvent()->{'MYPROPERTY'},
            'Additional properties are not copied to new exceptions'
        );
    }

    public function testStoreInstanceExistingRecurrenceId()
    {
        $vevent_exception = $this->generateRecurrentEvent();
        $event = new VObjectEvent($this->vcalendar);

        $recurrence_id = (string)$vevent_exception->{'RECURRENCE-ID'};
        $instance = $event->getEventInstance($recurrence_id);
        $instance->setSummary('I am an existing exception');

        $event->storeInstance($instance);

        // Check that base VEVENT is still there
        $base = $event->getEventInstance();
        $this->assertEquals(
           $this->vevent->SUMMARY,
           $base->getSummary()
       );

        $this->assertTrue($event->isException($recurrence_id));
    }

    public function testGetEventInstanceEmpty()
    {
        unset($this->vcalendar->VEVENT);

        $event = new VObjectEvent($this->vcalendar);
        $this->assertNull($event->getEventInstance());
    }

    public function testGetEventInstance()
    {
        $event = new VObjectEvent($this->vcalendar);

        $instance = $event->getEventInstance();
        $vevent = $instance->getInternalVEvent();
        $this->assertEquals($this->vevent, $vevent);
    }

    public function testGetEventInstanceBaseOnRecurrentEvent()
    {
        $this->generateRecurrentEvent();

        $event = new VObjectEvent($this->vcalendar);

        $instance = $event->getEventInstance();
        $vevent = $instance->getInternalVEvent();
        $this->assertEquals($this->vevent->serialize(), $vevent->serialize());
    }


    /** @expectedException \LogicException */
    public function testGetEventInstanceOnNonRecurrentEvent()
    {
        $event = new VObjectEvent($this->vcalendar);
        $instance = $event->getEventInstance('TEST');
    }

    public function testGetEventInstanceForExistingExceptionOnRecurrentEvent()
    {
        $exception = $this->generateRecurrentEvent();

        $event = new VObjectEvent($this->vcalendar);

        $recurrence_id = (string)$exception->{'RECURRENCE-ID'};

        $instance = $event->getEventInstance($recurrence_id);
        $vevent = $instance->getInternalVEvent();
        $this->assertEquals($exception->serialize(), $vevent->serialize());

        // Check that DTSTART is not modified, as the exception
        // already existed
        $this->assertEquals($exception->DTSTART->getDateTime(), $instance->getStart());
    }

    public function testGetEventInstanceForNonExistingExceptionOnRecurrentEvent()
    {
        $exception = $this->generateRecurrentEvent();

        $event = new VObjectEvent($this->vcalendar);

        $recurrence_id = '20150127T012345Z';

        $instance = $event->getEventInstance($recurrence_id);

        $this->assertTrue($instance->isException());
        $this->assertEquals($instance->getRecurrenceId(), $recurrence_id);

        // Check that start and end have been recalculated for this particular
        // recurrence instance
        $base_instance = $event->getEventInstance();
        $start = $base_instance->getStart();
        $start->modify('+7 days'); // Match the RECURRENCE-ID date
        $end = $base_instance->getEnd();
        $end->modify('+7 days'); // Match the RECURRENCE-ID date
        $this->assertEquals($start, $instance->getStart(), 'Start date is not updated');
        $this->assertEquals($end, $instance->getEnd(), 'End date is not updated');
    }

    protected function generateRecurrentEvent()
    {
        $this->vevent->RRULE = 'FREQ=DAILY';
        $this->vevent->DTSTART = '20150120T012345Z';

        // Event instance with RECURRENCE-ID, should not be returned as base
        // instance
        $vevent_exception = $this->vcalendar->add('VEVENT', [
            'UID' => $this->vevent->UID,
            'RECURRENCE-ID' => '20150121T012345Z',
            'DTSTART' => '20150121T100000Z',
            'ANOTHER-PROPERTY' => 'Check 2',
        ]);

        return $vevent_exception;
    }
}
