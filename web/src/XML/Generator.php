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

    protected $used_namespaces;


    /**
     * Creates a new XML generator
     *
     * @param boolean $formatted       Whether to format XML output or not (default: yes)
     */
    public function __construct($formatted = true)
    {
        $this->formatted = $formatted;
        $this->clearUsedNamespaces();
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
        $this->addUsedNamespace('DAV:');
        $propfind = $dom->createElementNS('DAV:', 'd:propfind');
        $prop = $this->propertyList('d:prop', $properties, $dom, false);

        $propfind->appendChild($prop);
        $dom->appendChild($propfind);

        $this->setXmlnsOnElement($propfind, $this->getUsedNamespaces());

        return $dom->saveXML();
    }

    /**
     * Generates a MKCALENDAR body XML
     *
     * @param array $properties Associative array, keys are in Clark notation
     * @return string XML body
     */
    public function mkCalendarBody(array $properties)
    {
        $dom = $this->emptyDocument();
        $this->addUsedNamespace('urn:ietf:params:xml:ns:caldav');
        $mkcalendar = $dom->createElementNS('urn:ietf:params:xml:ns:caldav', 'C:mkcalendar');
        if (count($properties) != 0) {
            $set = $dom->createElement('d:set');
            $prop = $this->propertyList('d:prop', $properties, $dom);


            $set->appendChild($prop);
            $mkcalendar->appendChild($set);
        }
        $dom->appendChild($mkcalendar);

        $this->setXmlnsOnElement($mkcalendar, $this->getUsedNamespaces());

        return $dom->saveXML();
    }

    /**
     * Generates the XML body for a PROPPATCH request
     *
     * @param array $properties Associative array, keys are in Clark notation
     * @return string XML body
     */
    public function proppatchBody(array $properties)
    {
        $dom = $this->emptyDocument();
        $this->addUsedNamespace('DAV:');
        $propertyupdate= $dom->createElementNS('DAV:', 'd:propertyupdate');
        $set = $dom->createElement('d:set');
        $prop = $this->propertyList('d:prop', $properties, $dom);

        $set->appendChild($prop);
        $propertyupdate->appendChild($set);
        $dom->appendChild($propertyupdate);

        $this->setXmlnsOnElement($propertyupdate, $this->getUsedNamespaces());

        return $dom->saveXML();
    }


    /**
     * Returns a <tag>[...]</tag> group of tags for a given list
     * of properties and values
     *
     * Doesn't modify the original document
     *
     * @param string $tag_name Wrapping tag name, including prefix
     * @param array $properties Associative array, keys are in Clark notation
     * @param \DOMDocument $document DOM document to be used to generate
     *                               new XML documents
     * @param bool $use_values  read values from the array too. Defaults to true
     * @return \DOMElement
     */
    protected function propertyList($tag_name, array $properties, \DOMDocument $document, $use_values = true)
    {
        $result = $document->createElement($tag_name);
        if (!$use_values) {
            $properties = array_flip($properties);
        }
        foreach ($properties as $property => $value) {
            list($ns, $name) = XMLUtil::parseClarkNotation($property);

            $this->addUsedNamespace($ns);

            $tag_name = $this->getPrefixForNamespace($ns) . ':' . $name;
            if ($use_values) {
                $element = $document->createElement($tag_name, $value);
            } else {
                $element = $document->createElement($tag_name);
            }
            $result->appendChild($element);
        }

        return $result;
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

        $this->clearUsedNamespaces();

        return $dom;
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
        $add_ns = self::$known_ns;
        if (count($only_ns) !== 0) {
            $add_ns = array_intersect_key(self::$known_ns, $only_ns);
        }

        foreach ($add_ns as $ns => $prefix) {
            $element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:' . $prefix, $ns);
        }

        return $element;
    }

    /**
     * Clears the list of used namespaces
     */
    protected function clearUsedNamespaces()
    {
        $this->used_namespaces = [];
    }

    /**
     * Adds a new used namespace
     *
     * @param string $namespace New namespace
     */
    protected function addUsedNamespace($namespace)
    {
        if (!isset(self::$known_ns[$namespace])) {
            self::$known_ns[$namespace] = 'x' . count(self::$known_ns);
        }
        $this->used_namespaces[$namespace] = self::$known_ns[$namespace];
    }

    /**
     * Returns the list of used namespaces
     *
     * @return array Used namespaces
     */
    protected function getUsedNamespaces()
    {
        return $this->used_namespaces;
    }

    /**
     * Returns the prefix for a namespace
     *
     * @param string $namespace Namespace
     * @return string prefix, false if not known
     */
    protected function getPrefixForNamespace($namespace)
    {
        return isset(self::$known_ns[$namespace])
            ? self::$known_ns[$namespace] : false;
    }
}
