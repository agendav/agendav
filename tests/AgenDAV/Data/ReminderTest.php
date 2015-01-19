<?php

namespace AgenDAV\Data;

class ReminderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromInput()
    {
        $input = [
            'position' => 2,
            'count' => 0,
            'unit' => 'minutes',
        ];

        $reminder = Reminder::createFromInput($input);

        $this->assertEquals(2, $reminder->getPosition());
        $this->assertEquals(
            new \DateInterval('PT0M'),
            $reminder->getWhen()
        );

        $input_2 = [
            'position' => '',
            'count' => 3,
            'unit' => 'hours',
        ];

        $reminder = Reminder::createFromInput($input_2);

        $this->assertNull($reminder->getPosition());
        $this->assertEquals(
            new \DateInterval('PT3H'),
            $reminder->getWhen()
        );
    }

    public function testGetParsedWhenAndISO8601String()
    {
        $reminder = new Reminder(new \DateInterval('PT0M'));
        $this->assertEquals(
            [0, 'minutes'],
            $reminder->getParsedWhen()
        );
        $this->assertEquals('-PT0M', $reminder->getISO8601String());

        $reminder = new Reminder(new \DateInterval('PT5M'));
        $this->assertEquals(
            [5, 'minutes'],
            $reminder->getParsedWhen()
        );
        $this->assertEquals('-PT5M', $reminder->getISO8601String());

        $reminder = new Reminder(new \DateInterval('PT5M'));

        // Different time unit
        $reminder = new Reminder(new \DateInterval('PT5H'));
        $this->assertEquals(
            [5, 'hours'],
            $reminder->getParsedWhen()
        );
        $this->assertEquals('-PT5H', $reminder->getISO8601String());

        $reminder = new Reminder(new \DateInterval('PT5M'));

        // Round to the next unit
        $reminder = new Reminder(new \DateInterval('PT60M'));
        $this->assertEquals(
            [1, 'hours'],
            $reminder->getParsedWhen()
        );
        $this->assertEquals('-PT1H', $reminder->getISO8601String());

        $reminder = new Reminder(new \DateInterval('PT5M'));

        // Round again (days -> months)
        $reminder = new Reminder(new \DateInterval('P28D'));
        $this->assertEquals(
            [1, 'months'],
            $reminder->getParsedWhen()
        );
        $this->assertEquals('-P28D', $reminder->getISO8601String());

        // Round (weeks -> days)
        $reminder = new Reminder(new \DateInterval('P14D'));
        $this->assertEquals(
            [2, 'weeks'],
            $reminder->getParsedWhen()
        );
        $this->assertEquals('-P14D', $reminder->getISO8601String());
    }
}
