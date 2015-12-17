<?php
namespace AgenDAV\CalDAV\Filter;

use Sabre\Xml\Writer;

class PrincipalPropertySearchTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneration()
    {
        $writer = new Writer();
        $writer->openMemory();
        $writer->setIndent(true);

        $principal_property_search = new PrincipalPropertySearch('abcdefg');

        $principal_property_search->addFilter($writer);

        $expected = <<<EOXML
<x1:principal-property-search test="anyof" xmlns:x1="DAV:">
 <x1:property-search xmlns:x1="DAV:">
  <x1:prop xmlns:x1="DAV:">
   <x2:calendar-user-address-set xmlns:x2="urn:ietf:params:xml:ns:caldav"/>
  </x1:prop>
  <x1:match xmlns:x1="DAV:">abcdefg</x1:match>
 </x1:property-search>
 <x1:property-search xmlns:x1="DAV:">
  <x1:prop xmlns:x1="DAV:">
   <x1:displayname xmlns:x1="DAV:"/>
  </x1:prop>
  <x1:match xmlns:x1="DAV:">abcdefg</x1:match>
 </x1:property-search>
 <x1:prop xmlns:x1="DAV:">
  <x1:displayname xmlns:x1="DAV:"/>
  <x1:email xmlns:x1="DAV:"/>
 </x1:prop>
</x1:principal-property-search>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $writer->outputMemory());
    }
}
