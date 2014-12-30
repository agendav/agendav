<?php
namespace AgenDAV\CalDAV\Resource;

use AgenDAV\Data\Share;

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

    public function testGetAllProperties()
    {
        $properties = [
            Calendar::DISPLAYNAME => 'Test',
            Calendar::CTAG => '123',
            '{urn:fake}attr' => 'value',
        ];
        $c = new Calendar('/path', $properties);

        $this->assertEquals(
            $c->getAllProperties(),
            $properties
        );

        // Test if getWritableProperties returns any readonly properties
        $writable_properties = $c->getWritableProperties();

        $this->assertArrayNotHasKey(
            Calendar::CTAG,
            $writable_properties,
            'Readonly properties are returned by getWritableProperties'
        );

    }

    public function testGetEmptyOrNullProperty()
    {
        $calendar = new Calendar(
            '/path',
            [
                'EMPTY' => '',
                'NULL' => null,
            ]
        );

        $this->assertNull($calendar->getProperty('EMPTY'));
        $this->assertNull($calendar->getProperty('NULL'));
    }

    public function testWritable()
    {
        $calendar = new Calendar('/url');
        $this->assertTrue($calendar->isWritable(), 'Calendars should be writable by default');

        $calendar->setWritable(false);
        $this->assertFalse($calendar->isWritable());
    }

    public function testShared()
    {
        $calendar = new Calendar('/url');
        $this->assertFalse($calendar->isSharedWithMe(), 'Calendars should not be marked as shared by default');

        $calendar->setShared(true);
        $this->assertTrue($calendar->isSharedWithMe());
    }

    public function testOwner()
    {
        $calendar = new Calendar('/url');

        $calendar->setOwner('jorge');
        $this->assertEquals('jorge', $calendar->getOwner());
    }

    public function testShares()
    {
        $calendar = new Calendar('/calendar1');

        $this->assertEquals([], $calendar->getShares(), 'Shares should start empty');
        $share_1 = new Share();
        $share_2 = new Share();

        $share_1->setSid(1);
        $share_1->setGrantor('jorge');
        $share_1->setGrantee('demo');
        $share_1->setPath('/calendar1');

        $share_2->setSid(2);
        $share_2->setGrantor('jorge');
        $share_2->setGrantee('second');
        $share_2->setPath('/calendar1');

        $shares = [ $share_1, $share_2 ];
        $calendar->setShares($shares);
        $this->assertCount(2, $calendar->getShares());

        $calendar->removeShare($share_1);
        $this->assertCount(1, $calendar->getShares());
        $remaining_share = $calendar->getShares();
        $remaining_share = current($remaining_share);
        $this->assertEquals(2, $remaining_share->getSid());

        $calendar->addShare($share_1);
        $this->assertCount(2, $calendar->getShares());
    }

}
