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

    public function testGetParsedWhen()
    {
        $reminder = new Reminder(new \DateInterval('PT0M'));
        $this->assertEquals(
            [0, 'minutes'],
            $reminder->getParsedWhen()
        );

        $reminder = new Reminder(new \DateInterval('PT5M'));
        $this->assertEquals(
            [5, 'minutes'],
            $reminder->getParsedWhen()
        );

        // Different time unit
        $reminder = new Reminder(new \DateInterval('PT5H'));
        $this->assertEquals(
            [5, 'hours'],
            $reminder->getParsedWhen()
        );

        // Round to the next unit
        $reminder = new Reminder(new \DateInterval('PT60M'));
        $this->assertEquals(
            [1, 'hours'],
            $reminder->getParsedWhen()
        );

        // Round again (days -> months)
        $reminder = new Reminder(new \DateInterval('P28D'));
        $this->assertEquals(
            [1, 'months'],
            $reminder->getParsedWhen()
        );
    }
}
