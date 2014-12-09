<?php
namespace AgenDAV\CalDAV\Resource;

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

}
