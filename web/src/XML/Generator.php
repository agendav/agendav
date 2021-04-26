<?php

namespace AgenDAV\XML;

use Sabre\Xml\Service as XMLUtil;
use Sabre\Xml\Writer;
use AgenDAV\CalDAV\ComponentFilter;
use AgenDAV\CalDAV\Share\ACL;
use AgenDAV\CalDAV\Filter\PrincipalPropertySearch;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
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
        $writer = $this->createNewWriter();
        $writer->startElement('{DAV:}acl');

        $this->generateAceTag($writer, 'owner', null, $acl->getOwnerPrivileges());
        $this->generateAceTag($writer, 'default', null, $acl->getDefaultPrivileges());

        $grants = $acl->getGrantsPrivileges();
        foreach ($grants as $principal => $privileges) {
            $this->generateAceTag($writer, 'grant', $principal, $privileges);
        }
        $writer->endElement();

        return $writer->outputMemory();
    }

    /**
     * Generates the REPORT XML body to get a list of principals that match a given filter
     *
     * @param \AgenDAV\CalDAV\Filter\PrincipalPropertySearch $filter
     * @return string
     */
    public function principalPropertySearchBody(PrincipalPropertySearch $filter)
    {
        $writer = $this->createNewWriter();

        $filter->addFilter($writer);

        return $writer->outputMemory();
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
        if ($use_values) {
            $elements = $properties;
        } else {
            $elements = new \Sabre\Xml\Element\Elements($properties);
        }

        $writer->write([ $tag_name => $elements ]);
    }

    /**
     * Generates an empty XML writer
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
     * @param \Sabre\Xml\Writer $writer
     * @param string $type one of 'owner', 'default' or 'grant'
     * @param string $principal Specific principal URL that this ACE affects
     * @param array $privileges
     * @return void
     */
    protected function generateAceTag(
        Writer $writer,
        $type,
        $principal = null,
        array $privileges
    )
    {
        $writer->startElement('{DAV:}ace');

        // Affected principals
        $this->generatePrincipalForAce($writer, $type, $principal);

        // List of privileges
        $writer->startElement('{DAV:}grant');
        foreach ($privileges as $privilege) {
            $writer->write([ '{DAV:}privilege' => [ $privilege => [] ] ]);
        }
        $writer->endElement(); // d:grant
        $writer->endElement(); // d:ace
    }

    /**
     * Returns a <principal> tag for an <ace>
     *
     * @param \Sabre\Xml\Writer $writer
     * @param string $type one of 'owner', 'default' or 'grant'
     * @param string $principal Used when $type is 'grant'
     */
    protected function generatePrincipalForAce(Writer $writer, $type, $principal = '')
    {
        $writer->startElement('{DAV:}principal');

        if ($type === 'owner') {
            $writer->write([
                '{DAV:}property' => [
                    '{DAV:}owner' => [],
                ]
            ]);
        }

        if ($type === 'default') {
            $writer->write([
                '{DAV:}authenticated' => [],
            ]);
        }

        if ($type === 'grant') {
            $writer->write([
                '{DAV:}href' => $principal,
            ]);
        }

        $writer->endElement();
    }

}
