<?php
namespace AgenDAV\CalDAV\Filter;

use Sabre\Xml\Writer;

class TimeRangeTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneration()
    {
        $writer = new Writer();
        $writer->openMemory();
        $writer->setIndent(true);

        $time_range = new TimeRange(
            '20141114T000000Z',
            '20141115T000000Z'
        );

        $time_range->addFilter($writer);

        $expected = <<<EOXML
<x1:time-range xmlns:x1="urn:ietf:params:xml:ns:caldav" start="20141114T000000Z" end="20141115T000000Z"/>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $writer->outputMemory());
    }
}
