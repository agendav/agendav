<?php
namespace AgenDAV\Data;

class CalendarTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateError1()
    {
        $c = new Calendar(array());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateError2()
    {
        $c = new Calendar(array('calendar' => 'X'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateError3()
    {
        $c = new Calendar(array('url' => 'X'));
    }

    public function testDefaults()
    {
        $defaults = Calendar::$defaults;
        $count = count($defaults) + 2; // url and calendar

        $c = new Calendar(array('calendar' => 'A', 'url' => 'B'));
        $current = $c->get();
        $this->assertCount($count, $current);
        $this->assertArrayHasKey('calendar', $current);
        $this->assertArrayHasKey('url', $current);
        foreach ($defaults as $d => $value) {
            $this->assertArrayHasKey($d, $current);
            $this->assertEquals($current[$d], $value);
        }
    }

    public function testSet()
    {
        $c = new Calendar(array('calendar' => 'A', 'url' => 'B'));
        $current = $c->get();
        $current['color'] = '#11223344';
        $current['displayname'] = 'Test';
        $c->set($current);

        $new_values = $c->get();
        $this->assertEquals($new_values, $current);
    }
}
