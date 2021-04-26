<?php
namespace AgenDAV\Event;

use PHPUnit\Framework\TestCase;

class RecurrenceIdTest extends TestCase
{

    /** @var \DateTime */
    private $datetime;

    const DATETIME_STRING = '2015-06-17 19:36:00';

    public function setUp()
    {
        $this->datetime = new \DateTimeImmutable(
            self::DATETIME_STRING,
            new \DateTimeZone('Europe/Madrid')
        );
    }

    public function testBasicGet()
    {
        $recurrence_id = new RecurrenceId($this->datetime);
        $this->assertTrue($this->datetime == $recurrence_id->getDateTime());
    }

    public function testSetString()
    {
        // Fixed UTC timestamp. Europe/Madrid has +0200 because of DST
        $recurrence_id = RecurrenceId::buildFromString('20150617T173600Z');
        $this->assertTrue($this->datetime == $recurrence_id->getDateTime());

        // Same with DATE value
        $recurrence_id = RecurrenceId::buildFromString('20150617');
        $datetime_allday = new \DateTime('2015-06-17', new \DateTimeZone('UTC'));
        $this->assertTrue($datetime_allday == $recurrence_id->getDateTime());
    }

    public function testGetString()
    {
        $recurrence_id = new RecurrenceId($this->datetime);
        $this->assertEquals(
            '20150617T173600Z',
            $recurrence_id->getString(),
            'getString() on RECURRENCE-IDs is not working'
        );

        $this->assertEquals(
            '20150617',
            $recurrence_id->getString(true),
            'getString() on all day RECURRENCE-IDs is not working'
        );
    }

    public function testMatchesDateTime()
    {
        $recurrence_id = new RecurrenceId($this->datetime);
        $this->assertTrue($recurrence_id->matchesDateTime($this->datetime));

        // Test with a different timezone
        $new_datetime = clone $this->datetime;
        $new_datetime->setTimeZone(new \DateTimeZone('Africa/Asmara'));
        $this->assertTrue(
            $recurrence_id->matchesDateTime($new_datetime),
            'matchesDateTime() can\'t handle different time zones'
        );
    }
}
