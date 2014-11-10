<?php

namespace AgenDAV\XML;

use Sabre\XML\Util as XMLUtil;

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
     * Generate formatted XML documents
     */
    protected $formatted;

    /**
     * Known namespaces
     */
    static $known_ns = array(
        'DAV:' => 'd',
        'urn:ietf:params:xml:ns:caldav' => 'C',
        'http://apple.com/ns/ical/' => 'A',
    );


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
        $dom = $this->emptyDocument();
        $propfind = $dom->createElementNS('DAV:', 'd:propfind');
        $prop = $dom->createElement('d:prop');

        $used_namespaces = array();

        foreach ($properties as $property) {
            list($ns, $name) = XMLUtil::parseClarkNotation($property);

            $this->addNamespacePrefix($ns);
            $used_namespaces[] = $ns;

            $element = $dom->createElement(self::$known_ns[$ns] . ':' . $name);
            $prop->appendChild($element);
        }

        $propfind->appendChild($prop);
        $dom->appendChild($propfind);

        $this->setXmlnsOnElement($propfind, $used_namespaces);

        return $dom->saveXML();
    }

    /**
     * Generates a MKCALENDAR body XML
     *
     * @param array $properties Associative array, keys are in Clark notation
     */
    public function mkCalendarBody(array $properties)
    {
        $dom = $this->emptyDocument();
        $mkcalendar = $dom->createElementNS('urn:ietf:params:xml:ns:caldav', 'C:mkcalendar');
        $set = $dom->createElement('d:set');
        $prop = $dom->createElement('d:prop');

        $used_namespaces = array();

        foreach ($properties as $property => $value) {
            list($ns, $name) = XMLUtil::parseClarkNotation($property);

            $this->addNamespacePrefix($ns);
            $used_namespaces[] = $ns;

            $element = $dom->createElement(self::$known_ns[$ns] . ':' . $name, $value);
            $prop->appendChild($element);
        }

        $set->appendChild($prop);
        $mkcalendar->appendChild($set);
        $dom->appendChild($mkcalendar);

        $this->setXmlnsOnElement($mkcalendar, $used_namespaces);

        return $dom->saveXML();
    }


    /**
     * Generates the base \DOMDocument
     *
     * @return \DOMDocument     Base document to start working on
     **/
    protected function emptyDocument()
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = $this->formatted;

        return $dom;
    }

    /**
     * Adds a common namespace prefix for unknown namespaces
     *
     * @return void
     **/
    private function addNamespacePrefix($namespace)
    {
        if (!isset(self::$known_ns[$namespace])) {
            self::$known_ns[$namespace] = 'x' . count(self::$known_ns);
        }
    }


    /**
     * Sets all namespaces on an element, using the $known_ns attribute
     *
     * @param \DOMElement $element      Element to be modified
     * @param array $only_ns            Add only these namespaces. If empty, it will add all known namespaces
     * @return \DOMElement              Same element that was provided
     **/
    protected function setXmlnsOnElement(\DOMElement $element, array $only_ns = array())
    {
        if (count($only_ns) !== 0) {
            $add_ns = array_intersect_key(self::$known_ns, array_flip($only_ns));
        } else {
            $add_ns = self::$known;
        }

        foreach ($add_ns as $ns => $prefix) {
            $element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:' . $prefix, $ns);
        }

        return $element;
    }
}

