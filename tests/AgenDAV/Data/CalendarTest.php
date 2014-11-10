<?php
namespace AgenDAV\Data;

class CalendarTest extends \PHPUnit_Framework_TestCase
{
    public function testSet()
    {
        $c = new Calendar('/path');
        $c->setProperty(Calendar::DISPLAYNAME,  'Test');

        $this->assertEquals($c->getProperty(Calendar::DISPLAYNAME), 'Test');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetUrl()
    {
        $c = new Calendar('/path');
        $c->setProperty('url',  '/should_not_change');
    }

}
