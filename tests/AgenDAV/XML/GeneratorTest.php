<?php
namespace AgenDAV\XML;

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

        $this->assertEquals(
            $body,
            '<?xml version="1.0" encoding="UTF-8"?>
<d:propfind xmlns:d="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:A="http://apple.com/ns/ical/" xmlns:x3="http://fake.namespace.org"><d:prop><d:resourcetype/><C:calendar-home-set/><A:calendar-color/><x3:calendar-color/></d:prop></d:propfind>'
        );
    }



    /**
     * Create a new XMLGenerator without output formatting
     **/
    public function createXMLGenerator()
    {
        return new Generator(false);
    }
}
