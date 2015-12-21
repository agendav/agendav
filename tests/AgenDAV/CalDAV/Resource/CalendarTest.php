<?php
namespace AgenDAV\CalDAV\Resource;

use AgenDAV\Data\Share;
use AgenDAV\Data\Principal;

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

    public function testOwner()
    {
        $calendar = new Calendar('/url');

        $principal = new Principal('/jorge');
        $calendar->setOwner($principal);
        $this->assertEquals($principal, $calendar->getOwner());
    }

    public function testShares()
    {
        $calendar = new Calendar('/calendar1');

        $this->assertEquals([], $calendar->getShares(), 'Shares should start empty');
        $share_1 = new Share();
        $share_2 = new Share();

        $share_1->setOwner('jorge');
        $share_1->setWith('demo');
        $share_1->setCalendar('/calendar1');

        $share_2->setOwner('jorge');
        $share_2->setWith('second');
        $share_2->setCalendar('/calendar1');

        $shares = [ $share_1, $share_2 ];
        $calendar->setShares($shares);
        $this->assertCount(2, $calendar->getShares());

        $calendar->removeShare($share_1);
        $this->assertCount(1, $calendar->getShares());
        $remaining_share = $calendar->getShares();
        $remaining_share = current($remaining_share);
        $this->assertEquals('second', $remaining_share->getWith());

        $calendar->addShare($share_1);
        $this->assertCount(2, $calendar->getShares());
    }

    public function testRgbaColor()
    {
        $calendar = new Calendar('/cal1');
        $calendar->setProperty(Calendar::COLOR, '#000000aa');
        $this->assertEquals('#000000aa', $calendar->getProperty(Calendar::COLOR));

        $calendar->setProperty(Calendar::COLOR, '#000000');
        $this->assertEquals('#000000ff', $calendar->getProperty(Calendar::COLOR));

        $calendar->setProperty(Calendar::COLOR, '#012');
        $this->assertEquals('#001122ff', $calendar->getProperty(Calendar::COLOR));
    }

}
