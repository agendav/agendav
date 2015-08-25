<?php

namespace AgenDAV\Event\Builder;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use AgenDAV\Event\RecurrenceId;
use Mockery as m;

class VObjectBuilderTest  extends \PHPUnit_Framework_TestCase
{
    protected $utc;

    protected $timezone;

    public function __construct()
    {
        $this->utc = new \DateTimeZone('UTC');
        $this->timezone = new \DateTimeZone('Europe/Madrid');
    }


    public function testCreateEvent()
    {
        $builder = new VObjectBuilder($this->utc);

        $event = $builder->createEvent('ABCDEFG');

        $this->assertEquals('ABCDEFG', $event->getUid());
    }

    public function testCreateEventInstanceWithNoRecurrenceId()
    {
        $builder = new VObjectBuilder($this->timezone);

        $event = $builder->createEvent('ABCDEFG');

        $instance = $builder->createEventInstanceFor($event);

        $this->assertEquals('ABCDEFG', $instance->getUid());
        $this->assertNull($instance->getRecurrenceId());
    }

    public function testCreateEventInstanceWithRecurrenceId()
    {
        $builder = new VObjectBuilder($this->timezone);

        $event = $builder->createEvent('ABCDEFG');
        $instance = $event->createEventInstance();
        $instance->setStart(new \DateTime('2015-08-25 18:10:00', $this->timezone));
        $instance->setSummary('Test event');
        $instance->setRepeatRule('FREQ=DAILY');
        $event->storeInstance($instance);

        $recurrence_id = RecurrenceId::buildFromString('20150825T171000Z');
        $exception = $builder->createEventInstanceFor($event, $recurrence_id);

        $this->assertEquals(
            $recurrence_id,
            $exception->getRecurrenceId()
        );
    }
}
