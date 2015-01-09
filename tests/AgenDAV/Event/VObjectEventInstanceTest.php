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
        'RECURRENCE-ID' => '20150109T123456',
    ];

    public function setUp()
    {
        $this->vcalendar = new VCalendar;
        $this->now = new \DateTime();
        $this->today_utc = new \DateTime('2015-01-09 00:00:00', new \DateTimeZone('UTC'));
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

        $this->checkSomeProperties($instance);
    }

    public function testGetStart()
    {
        $start = new \DateTime(
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
        $start = new \DateTime(
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
        $start = new \DateTime(
            '2015-01-31 16:00:00',
            new \DateTimeZone('Europe/Madrid')
        );
        $end = clone $start;
        $end->modify('+5 hours');

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
        $start = new \DateTime(
            '2015-01-31 16:00:00',
            new \DateTimeZone('Europe/Madrid')
        );

        $vevent = $this->vcalendar->add('VEVENT', [
            'DTSTART' => $start,
            'DURATION' => 'PT5H',
        ]);

        $instance = new VObjectEventInstance($vevent);
        $end = clone $start;
        $end->modify('+5 hours');
        $this->assertEquals(
            $end,
            $instance->getEnd()
        );
    }

    public function testEndNotDefined()
    {
        $start = new \DateTime(
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
        $start = new \DateTime(
            '2015-01-31',
            new \DateTimeZone('UTC')
        );
        $vevent_allday = $this->vcalendar->add('VEVENT', [
            'DTSTART' => $start,
        ]);
        $vevent_allday->DTSTART['VALUE'] = 'DATE';

        $instance = new VObjectEventInstance($vevent_allday);
        $end = clone $start;
        $end->modify('+1 day');
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
        $this->assertEquals($this->today_utc, $instance->getStart());
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
        $this->assertEquals((string)$vevent->SEQUENCE, '0');

        // Increment SEQUENCE
        $instance->touch();
        $this->assertEquals((string)$vevent->SEQUENCE, '1');
    }

    public function testCopyPropertiesFrom()
    {
        $vevent = $this->vcalendar->add('VEVENT', self::$some_properties);
        $instance = new VObjectEventInstance($vevent);
        $instance->setStart($this->now);

        $vevent_2 = $this->vcalendar->add('VEVENT');
        // copyPropertiesFrom() does not copy UID
        $vevent_2->UID = self::$some_properties['UID'];
        $instance_2 = new VObjectEventInstance($vevent_2);

        $instance_2->copyPropertiesFrom($instance);
        $this->checkSomeProperties($instance_2, false);
    }


    protected function checkSomeProperties(VObjectEventInstance $instance, $check_recurrence_id = true)
    {
        $this->assertEquals('12345', $instance->getUid());
        $this->assertEquals('Test summary', $instance->getSummary());
        $this->assertEquals('Test location', $instance->getLocation());
        $this->assertEquals('Test description', $instance->getDescription());
        $this->assertEquals('PUBLIC', $instance->getClass());
        $this->assertEquals('OPAQUE', $instance->getTransp());
        $this->assertEquals('FREQ=MONTHLY', $instance->getRepeatRule());
        if ($check_recurrence_id) {
            $this->assertEquals('20150109T123456', $instance->getRecurrenceId());
        }
    }
}
