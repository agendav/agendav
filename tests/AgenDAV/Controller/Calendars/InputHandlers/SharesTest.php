<?php
namespace AgenDAV\Controller\Calendars\InputHandlers;

class SharesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LengthException
     */
    public function testWrongLength()
    {
        $result = Shares::buildFromInput([ 'a' ], [ '0', '1' ]);
    }

    public function testTwoShares()
    {
        $with = [
            '/first/principal',
            '/second/principal',
        ];

        $rw = [ '0', '1' ];

        $result = Shares::buildFromInput($with, $rw);

        $this->assertCount(2, $result, 'buildFromInput does not generate the right number of Shares');
        $this->assertContainsOnlyInstancesOf('\AgenDAV\Data\Share', $result, 'Result contains non-Share objects');

        $this->assertEquals($with[0], $result[0]->getWith(), 'First generated share does not match provided with');
        $this->assertEquals($with[1], $result[1]->getWith(), 'Second generated share does not match provided with');

        $this->assertFalse($result[0]->isWritable(), 'First generated share was marked as writable');
        $this->assertTrue($result[1]->isWritable(), 'Second generated share was marked as non-writable');
    }
}
