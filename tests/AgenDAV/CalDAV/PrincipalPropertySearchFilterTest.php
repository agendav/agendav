<?php
namespace AgenDAV\CalDAV;

class PrincipalPropertySearchFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneration()
    {
        $document = new \DOMDocument('1.0', 'UTF-8');

        $principal_property_search = new PrincipalPropertySearchFilter('abcdefg');

        $principal_property_search_xml = $principal_property_search->generateFilterXML($document);
        $principal_property_search_xml->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:C', 'urn:ietf:params:xml:ns:caldav');

        $document->appendChild($principal_property_search_xml);
        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<principal-property-search xmlns:C="urn:ietf:params:xml:ns:caldav" test="anyof">
  <property-search>
    <prop>
      <C:calendar-user-address-set/>
      <match>abcdefg</match>
    </prop>
  </property-search>
  <property-search>
    <prop>
      <displayname/>
      <match>abcdefg</match>
    </prop>
  </property-search>
  <prop>
    <displayname/>
    <email/>
  </prop>
</principal-property-search>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $document->saveXML());
    }
}
