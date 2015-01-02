<?php

namespace AgenDAV\Data;

class RecurrenceTest extends \PHPUnit_Framework_TestCase
{
    /** @expectedException \InvalidArgumentException */
    public function testInvalidFrequency()
    {
        $recurrence = new Recurrence('MILISECONDLY');
    }

    public function testDefaultsAndSetters()
    {
        $recurrence = new Recurrence('DAILY');

        $this->assertEquals('DAILY', $recurrence->getFrequency());
        $this->assertNull($recurrence->getUntil());
        $this->assertNull($recurrence->getCount());
        $this->assertEquals(1, $recurrence->getInterval());

        $recurrence->setCount(10);
        $this->assertEquals(10, $recurrence->getCount());
        $recurrence->setCount(null);
        $this->assertNull($recurrence->getCount());

        $now = new \DateTime();
        $recurrence->setUntil($now);
        $this->assertEquals($now, $recurrence->getUntil());
        $recurrence->setUntil(null);
        $this->assertNull($recurrence->getUntil());
    }

    /** @expectedException \LogicException */
    public function testBothCountAndUntil()
    {
        $recurrence = new Recurrence('DAILY');
        $recurrence->setCount(10);
        $recurrence->setUntil(new \DateTime());
    }

    /** @expectedException \LogicException */
    public function testBothCountAndUntilReverseOrder()
    {
        $recurrence = new Recurrence('DAILY');
        $recurrence->setUntil(new \DateTime());
        $recurrence->setCount(10);
    }

    /** @expectedException \InvalidArgumentException */
    public function testSetInvalidCount()
    {
        $recurrence = new Recurrence('DAILY');
        $recurrence->setCount(0);
    }

    /** @expectedException \InvalidArgumentException */
    public function testSetInvalidInterval()
    {
        $recurrence = new Recurrence('DAILY');
        $recurrence->setInterval(0);
    }

    /** @expectedException \InvalidArgumentException */
    public function testInvalidCreateFromInput()
    {
        $recurrence = Recurrence::createFromInput([]);
    }

    public function testCreateFromInput()
    {
        $input = [
            'frequency' => 'DAILY',
            'interval' => '',
            'count' => '',
            'until' => '',
        ];
        $recurrence = Recurrence::createFromInput($input);

        $this->assertEquals('DAILY', $recurrence->getFrequency());
        $this->assertNull($recurrence->getUntil());
        $this->assertNull($recurrence->getCount());
        $this->assertEquals(1, $recurrence->getInterval());

        $input_2 = [
            'frequency' => 'WEEKLY',
            'interval' => '2',
            'count' => '4',
            'until' => '',
        ];
        $recurrence = Recurrence::createFromInput($input_2);

        $this->assertEquals('WEEKLY', $recurrence->getFrequency());
        $this->assertNull($recurrence->getUntil());
        $this->assertEquals(4, $recurrence->getCount());
        $this->assertEquals(2, $recurrence->getInterval());

        // Third test
        $until = new \DateTime('now', new \DateTimeZone('UTC'));
        $until_iso8601 = $until->format('Y-m-d\TH:i:s.u\Z');

        $input_3 = [
            'frequency' => 'YEARLY',
            'interval' => '',
            'count' => '',
            'until' => $until_iso8601,
        ];
        $recurrence = Recurrence::createFromInput($input_3);

        $this->assertEquals('YEARLY', $recurrence->getFrequency());
        $this->assertEquals($until, $recurrence->getUntil());
        $this->assertNull($recurrence->getCount());
        $this->assertEquals(1, $recurrence->getInterval());
    }
}
