<?php
namespace AgenDAV\CalDAV\Filter;

use \Sabre\Xml\Writer;

class UidTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneration()
    {
        $writer = new Writer();
        $writer->openMemory();
        $writer->setIndent(true);

        $uid_filter = new Uid('1234567890');
        $uid_filter->addFilter($writer);
        $expected = <<<XML
<x1:prop-filter name="UID" xmlns:x1="urn:ietf:params:xml:ns:caldav">
 <x1:text-match collation="i;octet" xmlns:x1="urn:ietf:params:xml:ns:caldav">1234567890</x1:text-match>
</x1:prop-filter>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $writer->outputMemory());
    }
}
