<?php

namespace AgenDAV\Event\Builder;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use AgenDAV\Event\RecurrenceId;
use Mockery as m;

class VObjectBuilderTest  extends \PHPUnit_Framework_TestCase
{
    const INPUT_DATETIME_FORMAT = 'Y-m-d\TH:i:s.u\Z';

    protected $utc;

    protected $timezone;

    protected $builder;

    public function __construct()
    {
        $this->utc = new \DateTimeZone('UTC');
        $this->timezone = new \DateTimeZone('Europe/Madrid');
    }

    public function setUp()
    {
        $this->builder = new VObjectBuilder($this->timezone);
    }


    public function testCreateEvent()
    {
        $event = $this->builder->createEvent('ABCDEFG');

        $this->assertEquals('ABCDEFG', $event->getUid());
    }

    public function testCreateEventInstanceWithNoRecurrenceId()
    {
        $event = $this->builder->createEvent('ABCDEFG');

        $instance = $this->builder->createEventInstanceFor($event);

        $this->assertEquals('ABCDEFG', $instance->getUid());
        $this->assertNull($instance->getRecurrenceId());
    }

    public function testCreateEventInstanceWithRecurrenceId()
    {
        $event = $this->builder->createEvent('ABCDEFG');
        $instance = $event->createEventInstance();
        $instance->setStart(new \DateTime('2015-08-25 18:10:00', $this->timezone));
        $instance->setSummary('Test event');
        $instance->setRepeatRule('FREQ=DAILY');
        $event->storeInstance($instance);

        $recurrence_id = RecurrenceId::buildFromString('20150825T171000Z');
        $exception = $this->builder->createEventInstanceFor($event, $recurrence_id);

        $this->assertEquals(
            $recurrence_id,
            $exception->getRecurrenceId()
        );
    }


    public function testCreateEventInstanceWithInputMostProperties()
    {
        $event = $this->builder->createEvent('ABCDEFG');

        $start = new \DateTime('2015-08-25 16:34:00', $this->utc);
        $end = new \DateTime('2015-08-25 17:34:00', $this->utc);

        $input = [
            'start' => $start->format(self::INPUT_DATETIME_FORMAT),
            'end' => $end->format(self::INPUT_DATETIME_FORMAT),
            'summary' => 'My test event',
            'location' => 'My test location',
            'description' => 'My test description',
            'class' => 'PRIVATE',
            'transp' => 'OPAQUE',
            'rrule' => 'FREQ=DAILY',
        ];

        $instance = $this->builder->createEventInstanceWithInput(
            $event,
            $input
        );

        // Check start value and timezone. Same for end
        $this->assertEquals($start, $instance->getStart());
        $this->assertEquals($this->timezone, $instance->getStart()->getTimeZone());
        $this->assertEquals($end, $instance->getEnd());
        $this->assertEquals($this->timezone, $instance->getEnd()->getTimeZone());
        $this->assertFalse($instance->isAllDay());

        $this->assertEquals($input['summary'], $instance->getSummary());
        $this->assertEquals($input['location'], $instance->getLocation());
        $this->assertEquals($input['description'], $instance->getDescription());
        $this->assertEquals($input['class'], $instance->getClass());
        $this->assertEquals($input['transp'], $instance->getTransp());
        $this->assertEquals($input['rrule'], $instance->getRepeatRule());
    }

    public function testCreateEventInstanceWithInputAllDay()
    {
        $event = $this->builder->createEvent('ABCDEFG');

        // 2015-08-25 all day event
        $start = new \DateTime('2015-08-25 00:00:00', $this->utc);
        $end = new \DateTime('2015-08-25 00:00:00', $this->utc);

        $input = [
            'start' => $start->format(self::INPUT_DATETIME_FORMAT),
            'end' => $end->format(self::INPUT_DATETIME_FORMAT),
            'allday' => 'true',
        ];

        $instance = $this->builder->createEventInstanceWithInput(
            $event,
            $input
        );

        $this->assertTrue($instance->isAllDay());
        $this->assertEquals($start, $instance->getStart());

        // All day events have DTEND = real end + 1 day
        $all_day_end = clone $end;
        $all_day_end->modify('+1 day');
        $this->assertEquals($all_day_end, $instance->getEnd());
    }

    public function testCreateEventInstanceWithInputRecurrenceId()
    {
        $event = $this->builder->createEvent('ABCDEFG');
        $instance = $event->createEventInstance();
        $instance->setStart(new \DateTime('2015-08-25 19:10:00', $this->timezone));
        $instance->setSummary('Test event');
        $instance->setRepeatRule('FREQ=DAILY');
        $event->storeInstance($instance);

        $start = new \DateTime('2015-08-26 18:00:00', $this->utc);
        $end = new \DateTime('2015-08-26 18:45:00', $this->utc);
        $input = [
            'start' => $start->format(self::INPUT_DATETIME_FORMAT),
            'end' => $end->format(self::INPUT_DATETIME_FORMAT),
            'summary' => 'My exception',
            'recurrence_id' => '20150826T174500Z',
            'rrule' => 'FREQ=DAILY',
        ];

        $instance = $this->builder->createEventInstanceWithInput(
            $event,
            $input
        );

        $this->assertTrue(
            $instance->isException(),
            'Built instance was not marked as exception'
        );
        $this->assertEmpty(
            $instance->getRepeatRule(),
            'RRULE is set on exceptions when building them from input'
        );
    }

    public function testCreateEventInstanceWithInputReminders()
    {
        $event = $this->builder->createEvent('ABCDEFG');

        $start = new \DateTime('2015-08-25 19:24:00', $this->utc);
        $end = new \DateTime('2015-08-25 20:24:00', $this->utc);

        $input = [
            'start' => $start->format(self::INPUT_DATETIME_FORMAT),
            'end' => $end->format(self::INPUT_DATETIME_FORMAT),
            'reminders' => [
                'count' => [ 0, 7 ],
                'unit' => [ 'minutes', 'days' ],
            ],
        ];

        $instance = $this->builder->createEventInstanceWithInput(
            $event,
            $input
        );

        $reminders = $instance->getReminders();

        $this->assertCount(2, $reminders, 'Total reminders do not match input');
        $this->assertEquals(
            [0, 'minutes'],
            $reminders[0]->getParsedWhen()
        );

        $this->assertEquals(
            [1, 'weeks'],
            $reminders[1]->getParsedWhen()
        );
    }
}
