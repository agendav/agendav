<?php

namespace AgenDAV\Event;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use AgenDAV\Data\Reminder;
use AgenDAV\Event\RecurrenceId;
use PHPUnit\Framework\TestCase;

/**
 * @author jorge
 */
class VObjectEventInstanceTest extends TestCase
{
    protected $vcalendar;

    protected $now;

    protected $today_utc;

    public static $some_properties = [
        'UID' => '12345',
        'SUMMARY' => 'Test summary',
        'LOCATION' => 'Test location',
        'DESCRIPTION' => 'Test description',
        'CLASS' => 'PUBLIC',
        'TRANSP' => 'OPAQUE',
        'RRULE' => 'FREQ=MONTHLY',
        'RECURRENCE-ID' => '20150109T123456Z',
        'SEQUENCE' => '2',
    ];

    public function setUp()
    {
        $this->vcalendar = new VCalendar;
        $this->now = new \DateTimeImmutable('2015-01-09 01:23:45');
        $this->today_utc = new \DateTimeImmutable('2015-01-09 00:00:00', new \DateTimeZone('UTC'));
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
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);

        $instance = new VObjectEventInstance($vevent);

        $this->checkSomeProperties($instance, self::$some_properties['RECURRENCE-ID']);
    }

    public function testGetStart()
    {
        $start = new \DateTimeImmutable(
            '2015-01-31 16:00:00',
            new \DateTimeZone('Europe/Madrid')
        );

        $vevent = $this->vcalendar->add('VEVENT', [
            'DTSTART' => $start,
        ]);

        $instance = new VObjectEventInstance($vevent);
        $this->assertEquals(
            $start,
            $instance->getStart()
        );
        $this->assertEquals(
            'Europe/Madrid',
            $instance->getStart()->getTimeZone()->getName()
        );
        $this->assertFalse(
            $instance->isAllDay()
        );
    }

    public function testIsAllDay()
    {
        $start = new \DateTimeImmutable(
            '2015-01-31 16:00:00',
            new \DateTimeZone('Europe/Madrid')
        );
        $vevent = $this->vcalendar->add('VEVENT');
        $vevent->DTSTART = $start;
        $vevent->DTSTART['VALUE'] = 'DATE';

        $instance = new VObjectEventInstance($vevent);
        $this->assertTrue(
            $instance->isAllDay()
        );
    }

    public function testEndNormal()
    {
        $start = new \DateTimeImmutable(
            '2015-01-31 16:00:00',
            new \DateTimeZone('Europe/Madrid')
        );
        $end = $start->modify('+5 hours');

        $vevent = $this->vcalendar->add('VEVENT', [
            'DTSTART' => $start,
            'DTEND' => $end,
        ]);

        $instance = new VObjectEventInstance($vevent);
        $this->assertEquals(
            $end,
            $instance->getEnd()
        );
    }

    public function testEndWithDuration()
    {
        $start = new \DateTimeImmutable(
            '2015-01-31 16:00:00',
            new \DateTimeZone('Europe/Madrid')
        );

        $vevent = $this->vcalendar->add('VEVENT', [
            'DTSTART' => $start,
            'DURATION' => 'PT5H',
        ]);

        $instance = new VObjectEventInstance($vevent);
        $end = $start->modify('+5 hours');
        $this->assertEquals(
            $end,
            $instance->getEnd()
        );
    }

    public function testEndNotDefined()
    {
        $start = new \DateTimeImmutable(
            '2015-01-31 16:00:00',
            new \DateTimeZone('Europe/Madrid')
        );

        $vevent = $this->vcalendar->add('VEVENT', [
            'DTSTART' => $start,
        ]);

        $instance = new VObjectEventInstance($vevent);
        $this->assertEquals(
            $start,
            $instance->getEnd()
        );

        // Same with an all day event
        $start = new \DateTimeImmutable(
            '2015-01-31',
            new \DateTimeZone('UTC')
        );
        $vevent_allday = $this->vcalendar->add('VEVENT', [
            'DTSTART' => $start,
        ]);
        $vevent_allday->DTSTART['VALUE'] = 'DATE';

        $instance = new VObjectEventInstance($vevent_allday);
        $end = $start->modify('+1 day');
        $this->assertEquals(
            $end,
            $instance->getEnd()
        );
    }

    public function testOneSet()
    {
        $vevent = $this->vcalendar->add('VEVENT', [
            'SUMMARY' => 'Test summary',
            'LOCATION' => 'Test location',
        ]);
        $instance = new VObjectEventInstance($vevent);
        $instance->setSummary('New summary');
        $instance->setLocation('');
        $this->assertEquals(
            'New summary',
            $instance->getSummary()
        );
        $this->assertEquals(
            '',
            $instance->getLocation()
        );
    }

    public function testSetStart()
    {
        $vevent = $this->vcalendar->add('VEVENT');
        $instance = new VObjectEventInstance($vevent);
        $instance->setStart($this->now);
        $this->assertEquals($this->now, $instance->getStart());
        $this->assertFalse($instance->isAllDay());

        $instance->setStart($this->today_utc, true);
        $this->assertEquals($this->today_utc, $instance->getStart(), 'UTC');
        $this->assertTrue($instance->isAllDay());
    }

    public function testSetEnd()
    {
        $vevent = $this->vcalendar->add('VEVENT');
        $instance = new VObjectEventInstance($vevent);
        $instance->setEnd($this->now);
        $this->assertEquals($this->now, $instance->getEnd());

        $vevent = $this->vcalendar->add('VEVENT');
        $instance = new VObjectEventInstance($vevent);
        $instance->setEnd($this->today_utc, true);
        $this->assertEquals($this->today_utc, $instance->getEnd());


        $vevent = $this->vcalendar->add('VEVENT', [
            'DURATION' => 'PT2H',
        ]);
        $instance = new VObjectEventInstance($vevent);
        $instance->setEnd($this->now);
        $this->assertNull($vevent->DURATION);
        $this->assertEquals($this->now, $instance->getEnd());
    }

    public function testTouch()
    {
        $vevent = $this->vcalendar->add('VEVENT');
        $instance = new VObjectEventInstance($vevent);
        $instance->touch();
        $this->assertNotNull($vevent->CREATED);
        $this->assertNotNull($vevent->DTSTAMP);
        $this->assertNotNull($vevent->{'LAST-MODIFIED'});
        $this->assertEquals('0', (string)$vevent->SEQUENCE);

        // Increment SEQUENCE
        $instance->touch();
        $this->assertEquals('1', (string)$vevent->SEQUENCE);
    }

    public function testCopyPropertiesFrom()
    {
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);
        $this->setSomeVAlarms($vevent);
        $instance = new VObjectEventInstance($vevent);
        $instance->setStart($this->now);

        $vevent_2 = $this->vcalendar->add('VEVENT');
        // copyPropertiesFrom() does not copy UID
        $vevent_2->UID = self::$some_properties['UID'];
        $instance_2 = new VObjectEventInstance($vevent_2);

        $instance_2->copyPropertiesFrom($instance);
        $this->checkSomeProperties($instance_2);

        // copyPropertiesFrom should not touch SEQUENCE
        $this->assertEquals(null, $vevent_2->SEQUENCE, 'SEQUENCE should not be updated when copying properties');

        // Reminders should be copied to the resulting instance
        $this->assertEquals(
            $instance->getReminders(),
            $instance_2->getReminders()
        );
    }

    public function testCopyPropertiesOnRecurrenceException()
    {
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);
        $instance = new VObjectEventInstance($vevent);
        $instance->setStart($this->now);

        $vevent_2 = $this->vcalendar->add('VEVENT');
        // copyPropertiesFrom() does not copy UID
        $vevent_2->UID = self::$some_properties['UID'];
        $vevent_2->{'RECURRENCE-ID'} = '20150302T001122Z';
        $instance_2 = new VObjectEventInstance($vevent_2);
        $instance_2->markAsException();

        $instance_2->copyPropertiesFrom($instance);
        $this->checkSomeProperties($instance_2, '20150302T001122Z');

        // copyPropertiesFrom should not copy RRULE
        $this->assertEmpty(
            $instance_2->getRepeatRule(),
            'RRULE should not be set when copying properties on recurrence exceptions');
    }

    public function testGetReminders()
    {
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);

        $this->setSomeVAlarms($vevent);

        $instance = new VObjectEventInstance($vevent);
        $reminders = $instance->getReminders();

        $this->assertCount(1, $reminders);
        $reminder = $reminders[0];

        $this->assertEquals([10, 'minutes'], $reminder->getParsedWhen());
        $this->assertEquals(2, $reminder->getPosition());
    }

    /**
     * Tests a TRIGGER:0PTS VALARM, which is an alarm set to
     * 'when the start events' by some CalDAV clients
     */
    public function testGetRemindersOnStart()
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//dmfs.org//mimedir.icalendar//EN
BEGIN:VEVENT
DTSTART;TZID=Europe/Madrid:20150226T220700
SUMMARY:A test event
UID:be1554d5-f84c-46d3-aa2d-13d8df860826
BEGIN:VALARM
TRIGGER;VALUE=DURATION:PT0S
ACTION:DISPLAY
DESCRIPTION:Default Event Notification
X-WR-ALARMUID:bbea860c-9c38-4b8d-95f8-b28721327d87
END:VALARM
END:VEVENT
END:VCALENDAR
ICS;

        $this->vcalendar = \Sabre\VObject\Reader::read($ics);
        $vevent = $this->vcalendar->VEVENT[0];

        $instance = new VObjectEventInstance($vevent);
        $reminders = $instance->getReminders();

        $this->assertCount(1, $reminders);
        $reminder = $reminders[0];

        $this->assertEquals([0, 'minutes'], $reminder->getParsedWhen());
        $this->assertEquals(1, $reminder->getPosition());
    }

    public function testClearReminders()
    {
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);

        $this->setSomeVAlarms($vevent);

        $instance = new VObjectEventInstance($vevent);
        $instance->clearReminders();

        $reminders = $instance->getReminders();

        $this->assertCount(0, $instance->getReminders());
        $this->assertCount(2, $vevent->VALARM);
    }

    public function testAddReminder()
    {
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);
        $this->setSomeVAlarms($vevent);

        $instance = new VObjectEventInstance($vevent);
        $instance->addReminder(
            new Reminder(new \DateInterval('P3D'), 34)
        );

        $reminders = $instance->getReminders();

        $this->assertCount(2, $reminders);

        $new_reminder = $reminders[1];
        $this->assertEquals([3, 'days'], $new_reminder->getParsedWhen());
        $this->assertEquals(4, $new_reminder->getPosition(), 'addReminder did not update the reminder position');
    }

    public function testSetRepeatRuleShouldMakeItRecurrent()
    {
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);
        unset($vevent->RRULE);
        $instance = new VObjectEventInstance($vevent);
        $this->assertFalse($instance->isRecurrent());

        $instance->setRepeatRule('FREQ=DAILY');
        $this->assertTrue($instance->isRecurrent(), 'After adding a RRULE, the instance should be marked as recurrent');
    }

    public function testUpdateForRecurrenceId()
    {
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);

        $instance = new VObjectEventInstance($vevent);
        $start = new \DateTimeImmutable('2015-03-13 21:00:00', new \DateTimeZone('Europe/Madrid'));
        $end = new \DateTimeImmutable('2015-03-13 21:45:00', new \DateTimeZone('Europe/Madrid'));
        $instance->setStart($start);
        $instance->setEnd($end);

        $new_start = $start->modify('+1 month');
        $new_end = $end->modify('+1 month');

        $recurrence_id = RecurrenceId::buildFromString('20150413T200000Z');
        $instance->updateForRecurrenceId($recurrence_id);
        $this->assertEquals($new_start, $instance->getStart());
        $this->assertEquals($new_end, $instance->getEnd());
    }

    public function testUpdateForRecurrenceIdAllDay()
    {
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);

        $instance = new VObjectEventInstance($vevent);
        $start = new \DateTimeImmutable('2015-03-17', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable('2015-03-18', new \DateTimeZone('UTC'));
        $instance->setStart($start, true);
        $instance->setEnd($end, true);

        // Generate new dates for this recurrence
        $new_start = $start->modify('+1 month');
        $new_end = $end->modify('+1 month');

        $recurrence_id = RecurrenceId::buildFromString('20150417');
        $instance->updateForRecurrenceId($recurrence_id);
        $this->assertEquals($new_start, $instance->getStart());
        $this->assertEquals($new_end, $instance->getEnd());
    }

    public function testSetRecurrenceIdDateTime()
    {
        $vevent = $this->vcalendar->add('VEVENT');
        $instance = new VObjectEventInstance($vevent);

        $instance->setStart(new \DateTimeImmutable());
        $instance->setRepeatRule('FREQ=DAILY');
        $recurrence_id = RecurrenceId::buildFromString('20150318T001122Z');
        $instance->setRecurrenceId($recurrence_id);

        $recurrence_id = $vevent->{'RECURRENCE-ID'};
        $this->assertNull($recurrence_id['VALUE'], 'setRecurrenceId() does not set VALUE=DATE-TIME');
    }

    public function testSetRecurrenceIdDate()
    {
        $vevent = $this->vcalendar->add('VEVENT');
        $instance = new VObjectEventInstance($vevent);

        $instance->setStart(new \DateTimeImmutable(), true);
        $instance->setRepeatRule('FREQ=DAILY');
        $recurrence_id = RecurrenceId::buildFromString('20150318');
        $instance->setRecurrenceId($recurrence_id);

        $recurrence_id = $vevent->{'RECURRENCE-ID'};
        $this->assertEquals(
            'DATE',
            $recurrence_id['VALUE'],
            'setRecurrenceId() does not set VALUE=DATE for all day events'
        );
    }

    public function testSetRecurrenceIdNull()
    {
        $vevent = $this->vcalendar->add('VEVENT');
        $instance = new VObjectEventInstance($vevent);

        $instance->setStart(new \DateTimeImmutable(), true);
        $instance->setRepeatRule('FREQ=DAILY');
        $instance->setRecurrenceId(RecurrenceId::buildFromString('20150701T001122Z'));
        $instance->setRecurrenceId(null);

        $recurrence_id = $vevent->{'RECURRENCE-ID'};
        $this->assertNull($recurrence_id, 'setRecurrenceId(null) has no effect');
    }


    public function testHasExceptions()
    {
        $vevent = $this->vcalendar->add('VEVENT');
        $instance = new VObjectEventInstance($vevent);

        $this->assertFalse(
            $instance->hasExceptions(),
            'Default hasExceptions() for event instances should be false'
        );

        $instance->setHasExceptions();

        $this->assertTrue(
            $instance->hasExceptions(),
            'setHasExceptions() for event instances does not mark the instance'
        );
    }


    protected function checkSomeProperties(VObjectEventInstance $instance, $recurrence_id = false)
    {
        $this->assertEquals('12345', $instance->getUid());
        $this->assertEquals('Test summary', $instance->getSummary());
        $this->assertEquals('Test location', $instance->getLocation());
        $this->assertEquals('Test description', $instance->getDescription());
        $this->assertEquals('PUBLIC', $instance->getClass());
        $this->assertEquals('OPAQUE', $instance->getTransp());
        if ($recurrence_id !== false) {
            $recurrence_id_wrapper = RecurrenceId::buildFromString($recurrence_id);
            $this->assertEquals($recurrence_id_wrapper, $instance->getRecurrenceId());
        } else {
            $this->assertEquals('FREQ=MONTHLY', $instance->getRepeatRule());
        }
    }

    protected function setSomeVAlarms(VEvent $vevent)
    {
        // Not recognized by AgenDAV
        $valarm = $vevent->add('VALARM', [
            'ACTION' => 'DISPLAY',
            'TRIGGER' => '-P1DT10M',
        ]);
        $valarm->TRIGGER['RELATED'] = 'END';

        // Recognized by AgenDAV
        $vevent->add('VALARM', [
            'ACTION' => 'DISPLAY',
            'TRIGGER' => '-PT10M',
        ]);

        // Not recognized by AgenDAV
        $valarm = $vevent->add('VALARM', [
            'ACTION' => 'DISPLAY',
        ]);
        $valarm->TRIGGER = '20150120T123000';
        $valarm->TRIGGER['VALUE'] = 'DATE-TIME';
    }
}
