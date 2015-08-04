<?php
namespace AgenDAV\CalDAV\Filter;

class PrincipalPropertySearchTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneration()
    {
        $document = new \DOMDocument('1.0', 'UTF-8');

        $principal_property_search = new PrincipalPropertySearch('abcdefg');

        $principal_property_search_xml = $principal_property_search->generateFilterXML($document);
        $principal_property_search_xml->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:C', 'urn:ietf:params:xml:ns:caldav');
        $principal_property_search_xml->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d', 'DAV:');

        $document->appendChild($principal_property_search_xml);
        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<d:principal-property-search xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" test="anyof">
  <d:property-search>
    <d:prop>
      <C:calendar-user-address-set/>
      <d:match>abcdefg</d:match>
    </d:prop>
  </d:property-search>
  <d:property-search>
    <d:prop>
      <d:displayname/>
      <d:match>abcdefg</d:match>
    </d:prop>
  </d:property-search>
  <d:prop>
    <d:displayname/>
    <d:email/>
  </d:prop>
</d:principal-property-search>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $document->saveXML());
    }
}
