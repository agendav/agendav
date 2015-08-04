<?php
namespace AgenDAV\CalDAV\Filter;

class TimeRangeTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneration()
    {
        $document = new \DOMDocument('1.0', 'UTF-8');

        $time_range = new TimeRange(
            '20141114T000000Z',
            '20141115T000000Z'
        );

        $time_range_element = $time_range->generateFilterXML($document);
        $time_range_element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:C', 'urn:ietf:params:xml:ns:caldav');

        $document->appendChild($time_range_element);
        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<C:time-range xmlns:C="urn:ietf:params:xml:ns:caldav" start="20141114T000000Z" end="20141115T000000Z"/>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $document->saveXML());
    }
}
