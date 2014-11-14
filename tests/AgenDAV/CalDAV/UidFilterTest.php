<?php
namespace AgenDAV\CalDAV;

class UidFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneration()
    {
        $document = new \DOMDocument('1.0', 'UTF-8');

        $uid_filter = new UidFilter('1234567890');

        $uid_filter_element = $uid_filter->generateFilterXML($document);
        $uid_filter_element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:C', 'urn:ietf:params:xml:ns:caldav');

        $document->appendChild($uid_filter_element);
        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<C:prop-filter xmlns:C="urn:ietf:params:xml:ns:caldav" name="UID">
  <C:text-match collation="i;octet">1234567890</C:text-match>
</C:prop-filter>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $document->saveXML());
    }
}
