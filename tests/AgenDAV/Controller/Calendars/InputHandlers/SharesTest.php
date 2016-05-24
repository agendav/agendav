<?php
namespace AgenDAV\Controller\Calendars\InputHandlers;

class SharesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LengthException
     */
    public function testWrongLength()
    {
        $result = Shares::buildFromInput([ 'a' ], [ '0', '1' ], '/owner', '/calendar');
    }

    public function testTwoShares()
    {
        $with = [
            '/first/principal',
            '/second/principal',
        ];

        $rw = [ '0', '1' ];

        $result = Shares::buildFromInput($with, $rw, '/owner', '/calendar');

        $this->assertCount(2, $result, 'buildFromInput does not generate the right number of Shares');
        $this->assertContainsOnlyInstancesOf('\AgenDAV\Data\Share', $result, 'Result contains non-Share objects');

        $this->assertEquals('/owner', $result[0]->getOwner(), 'First generated share did not store calendar owner');
        $this->assertEquals('/owner', $result[1]->getOwner(), 'Second generated share did not store calendar owner');

        $this->assertEquals('/calendar', $result[0]->getCalendar(), 'First generated share did not store calendar');
        $this->assertEquals('/calendar', $result[1]->getCalendar(), 'Second generated share did not store calendar');

        $this->assertEquals($with[0], $result[0]->getWith(), 'First generated share does not match provided with');
        $this->assertEquals($with[1], $result[1]->getWith(), 'Second generated share does not match provided with');

        $this->assertFalse($result[0]->isWritable(), 'First generated share was marked as writable');
        $this->assertTrue($result[1]->isWritable(), 'Second generated share was marked as non-writable');
    }
}
