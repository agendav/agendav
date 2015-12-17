<?php

namespace AgenDAV\XML;

use Sabre\Xml\Service as XMLUtil;
use Sabre\Xml\Writer;
use AgenDAV\CalDAV\ComponentFilter;
use AgenDAV\CalDAV\Share\ACL;
use AgenDAV\CalDAV\Filter\PrincipalPropertySearch;

/*
 * Copyright 2014 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Helper class to generate XML
 *
 */
class Generator
{
     /**
      * Default namespace prefixes
      */
     public static $default_ns = [
         'DAV:' => 'd',
         'urn:ietf:params:xml:ns:caldav' => 'C',
         'http://apple.com/ns/ical/' => 'A',
     ];

    /**
     * Generate formatted XML documents
     */
    protected $formatted;

    /**
     * Creates a new XML generator
     *
     * @param boolean $formatted       Whether to format XML output or not (default: yes)
     */
    public function __construct($formatted = true)
    {
        $this->formatted = $formatted;
    }

    /**
     * Generates a PROPFIND body
     *
     * @param array $properties List of properties, specified using Clark notation
     * @return string           XML body for the propfind request
     **/
    public function propfindBody(array $properties)
    {
        $writer = $this->createNewWriter();
        $writer->startElement('{DAV:}propfind');
        $this->addPropertiesList($writer, '{DAV:}prop', $properties, false);
        $writer->endElement();

        return $writer->outputMemory();
    }

    /**
     * Generates a MKCALENDAR body XML
     *
     * @param array $properties Associative array, keys are in Clark notation
     * @return string XML body
     */
    public function mkCalendarBody(array $properties)
    {
        $writer = $this->createNewWriter();
        $writer->startElement('{urn:ietf:params:xml:ns:caldav}mkcalendar');

        if (count($properties) != 0) {
            $writer->startElement('{DAV:}set');
            $this->addPropertiesList($writer, '{DAV:}prop', $properties);
            $writer->endElement();
        }

        $writer->endElement();

        return $writer->outputMemory();
    }

    /**
     * Generates the XML body for a PROPPATCH request
     *
     * @param array $properties Associative array, keys are in Clark notation
     * @return string XML body
     */
    public function proppatchBody(array $properties)
    {
        $writer = $this->createNewWriter();
        $writer->startElement('{DAV:}propertyupdate');
        $writer->startElement('{DAV:}set');

        $this->addPropertiesList($writer, '{DAV:}prop', $properties);

        $writer->endElement();
        $writer->endElement();

        return $writer->outputMemory();
    }

    /**
     * Generates the REPORT XML body to get a list of events within a given range
     *
     * @param \AgenDAV\CalDAV\ComponentFilter $component_filter Filter for this report
     * @return string
     */
    public function calendarQueryBody(\AgenDAV\CalDAV\ComponentFilter $component_filter)
    {
        $writer = $this->createNewWriter();
        $writer->startElement('{urn:ietf:params:xml:ns:caldav}calendar-query');

        // Usual properties we need from events
        $properties = [
            '{DAV:}getetag',
            '{urn:ietf:params:xml:ns:caldav}calendar-data',
        ];
        $this->addPropertiesList($writer, '{DAV:}prop', $properties, false);

        $writer->startElement('{urn:ietf:params:xml:ns:caldav}filter');
        $writer->startElement('{urn:ietf:params:xml:ns:caldav}comp-filter');
        $writer->writeAttribute('name', 'VCALENDAR');
        $writer->startElement('{urn:ietf:params:xml:ns:caldav}comp-filter');
        $writer->writeAttribute('name', 'VEVENT');

        $component_filter->addFilter($writer);

        $writer->endElement();
        $writer->endElement();

        $writer->endElement(); // C:filter
        $writer->endElement(); // C:calendar-query

        return $writer->outputMemory();
    }

    /**
     * Generates an XML body suitable for an ACL operation
     *
     * @param \AgenDAV\CalDAV\Share\ACL $acl ACL definition to be applied
     * @return string XML generated body
     */
    public function aclBody(ACL $acl)
    {
        $dom = $this->createNewWriter();
        $this->addUsedNamespace('DAV:');
        $acl_elem = $dom->createElementNS('DAV:', 'd:acl');

        $dom->appendChild($acl_elem);

        $ace_owner = $this->generateAceTag($dom, 'owner', null, $acl->getOwnerPrivileges());
        $acl_elem->appendChild($ace_owner);

        $ace_default = $this->generateAceTag($dom, 'default', null, $acl->getDefaultPrivileges());
        $acl_elem->appendChild($ace_default);

        $grants = $acl->getGrantsPrivileges();
        foreach ($grants as $principal => $privileges) {
            $ace_grant = $this->generateAceTag($dom, 'grant', $principal, $privileges);
            $acl_elem->appendChild($ace_grant);
        }

        $this->setXmlnsOnElement($acl_elem, $this->getUsedNamespaces());
        return $dom->saveXML();
    }

    /**
     * Generates the REPORT XML body to get a list of principals that match a given filter
     *
     * @param \AgenDAV\CalDAV\Filter\PrincipalPropertySearch $filter
     * @return string
     */
    public function principalPropertySearchBody(PrincipalPropertySearch $filter)
    {
        $dom = $this->createNewWriter();
        $this->addUsedNamespace('DAV:');
        $this->addUsedNamespace('urn:ietf:params:xml:ns:caldav');

        $conditions_xml = $filter->generateFilterXML($dom);
        $dom->appendChild($conditions_xml);
        $this->setXmlnsOnElement($conditions_xml, $this->getUsedNamespaces());

        return $dom->saveXML();
    }


    /**
     * Adds a list of tags with a <tag_name> root tag to the passed Writer
     *
     * @param Sabre\Xml\Writer XML writer onto we want to add this list
     * @param string $tag_name Wrapping tag name, in Clark notation
     * @param array $properties Associative array of properties, keys are in Clark notation
     * @param bool $use_values  read values from the array too. Defaults to true
     * @return void
     */
    protected function addPropertiesList(Writer $writer, $tag_name, array $properties, $use_values = true)
    {
        $writer->startElement($tag_name);
        if (!$use_values) {
            $properties = array_flip($properties);
        }

        foreach ($properties as $property => $value) {
            if ($use_values) {
                $writer->writeElement($property, $value);
            } else {
                $writer->writeElement($property);
            }
        }

        $writer->endElement();
    }

    /**
     * Generates the base \DOMDocument
     *
     * @return \Sabre\XML\Writer     Base document to start working on
     **/
    protected function createNewWriter()
    {
        $writer = new Writer();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent($this->formatted);
        $writer->namespaceMap = self::$default_ns;

        return $writer;
    }


    /**
     * Generates a <d:ace> element, which is used on ACLs
     *
     * @param \DOMDocument $document
     * @param string $type one of 'owner', 'default' or 'grant'
     * @param array $privileges
     */
    protected function generateAceTag(
        \DOMDocument $document,
        $type,
        $principal = null,
        array $privileges
    )
    {
        $ace = $document->createElement('d:ace');

        // Affected principals
        $principal = $this->generatePrincipalForAce($document, $type, $principal);
        $ace->appendChild($principal);

        // List of privileges
        $grant = $this->propertyList('d:grant', $privileges, $document, false);

        $ace->appendChild($grant);

        return $ace;
    }

    /**
     * Returns a <principal> tag for an <ace>
     *
     * @param \DOMDocument $document
     * @param string $type one of 'owner', 'default' or 'grant'
     * @param string $principal Used when $type is 'grant'
     * @return \DOMElement
     */
    protected function generatePrincipalForAce(\DOMDocument $document, $type, $principal = '')
    {
        $principal_elem = $document->createElement('d:principal');

        if ($type === 'owner') {
            $property = $document->createElement('d:property');
            $owner = $document->createElement('d:owner');
            $property->appendChild($owner);
            $principal_elem->appendChild($property);
        }

        if ($type === 'default') {
            $authenticated = $document->createElement('d:authenticated');
            $principal_elem->appendChild($authenticated);
        }

        if ($type === 'grant') {
            $href = $document->createElement('d:href', $principal);
            $principal_elem->appendChild($href);
        }

        return $principal_elem;
    }

}
