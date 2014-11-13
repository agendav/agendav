<?php
namespace AgenDAV\XML;

use AgenDAV\Data\Calendar;

/**
 * @author jorge
 */
class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testPropfindBody()
    {
        $generator = $this->createXMLGenerator();

        $body = trim($generator->propfindBody(array(
            '{DAV:}resourcetype',
            '{urn:ietf:params:xml:ns:caldav}calendar-home-set',
            '{http://apple.com/ns/ical/}calendar-color',
            '{http://fake.namespace.org}calendar-color'
        )));

        $expected = '<?xml version="1.0" encoding="UTF-8"?>
<d:propfind xmlns:d="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:A="http://apple.com/ns/ical/" xmlns:x3="http://fake.namespace.org"><d:prop><d:resourcetype/><C:calendar-home-set/><A:calendar-color/><x3:calendar-color/></d:prop></d:propfind>';

        $this->assertXmlStringEqualsXmlString($expected, $body);
    }

    public function testMkCalendarBody()
    {
        $generator = $this->createXMLGenerator();

        $properties = [
            Calendar::DISPLAYNAME => 'Calendar name',
            '{urn:fake}attr' => 'value',
        ];

        $body = $generator->mkCalendarBody($properties);

        $expected = '<?xml version="1.0" encoding="UTF-8"?>
<C:mkcalendar xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" xmlns:x4="urn:fake"><d:set><d:prop><d:displayname>Calendar name</d:displayname><x4:attr>value</x4:attr></d:prop></d:set></C:mkcalendar>';

        $this->assertXmlStringEqualsXmlString($expected, $body);
    }

    public function testproppatchBody()
    {
        $generator = $this->createXMLGenerator();

        $properties = [
            Calendar::DISPLAYNAME => 'Calendar name',
            Calendar::COLOR => '#f0f0f0aa',
            '{urn:fake}attr' => 'value',
        ];

        $body = $generator->proppatchBody($properties);

        $expected = '<?xml version="1.0" encoding="UTF-8"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:A="http://apple.com/ns/ical/" xmlns:x4="urn:fake"><d:set><d:prop><d:displayname>Calendar name</d:displayname><A:calendar-color>#f0f0f0aa</A:calendar-color><x4:attr>value</x4:attr></d:prop></d:set></d:propertyupdate>';

        $this->assertXmlStringEqualsXmlString($expected, $body);
    }


    /**
     * Create a new XMLGenerator without output formatting
     **/
    public function createXMLGenerator()
    {
        return new Generator(false);
    }
}
