<?php
namespace AgenDAV\Data;

class CalendarTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $defaults = Calendar::$defaults;
        $count = count($defaults) + 2; // url and calendar

        $c = new Calendar('/path');
        $current = $c->getAll();
        $this->assertCount($count, $current);
        $this->assertArrayHasKey('calendar', $current);
        $this->assertArrayHasKey('url', $current);
        foreach ($defaults as $d => $value) {
            $this->assertArrayHasKey($d, $current);
            $this->assertEquals($current[$d], $value);
        }
    }

    public function testCalendarAttribute()
    {
        $c = new Calendar('TEST');
        $this->assertEquals($c->url, 'TEST');
        $this->assertEquals($c->calendar, 'TEST');

        $all = $c->getAll();
        $this->assertEquals($c->url, 'TEST');
        $this->assertEquals($c->calendar, 'TEST');
    }

    public function testSet()
    {
        $c = new Calendar('/path');
        $c->displayname = 'Test';

        $this->assertEquals($c->displayname, 'Test');
    }

}
