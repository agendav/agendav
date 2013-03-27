<?php
namespace AgenDAV\CalDAV;

use AgenDAV\Data\Permissions;
use AgenDAV\Data\SinglePermission;
use AgenDAV\CalDAVACLGeneratorTest;

class ACLGeneratorTest extends \PHPUnit_Framework_TestCase
{

    private $permissions;

    public function setUp()
    {
        $this->defaults = array(
            new SinglePermission(
                array('urn:ietf:params:xml:ns:caldav', 'read-free-busy')
            ),
        );
        $this->perms = array(
            'owner' => array(
                new SinglePermission(array('DAV:', 'all')),
            ),
            'readwrite' => array(
                new SinglePermission(array('DAV:', 'read')),
                new SinglePermission(array('DAV:', 'write')),
            ),
        );

        $this->permissions = new Permissions($this->defaults);
        foreach ($this->perms as $profile => $p) {
            $this->permissions->addProfile($profile, $p);
        }
    }

    public function testBuildOne()
    {
        $aclgen = new ACLGenerator($this->permissions);
        $aclgen->addGrant('/test/', 'readwrite');
        $built_acl = $aclgen->buildACL();

        // Values: array(occurrences, children)
        // We are using now a default namespace, so we have to prefix each entry
        $expected = array(
            '/DAV:acl' => array(1, 3),
            '/DAV:acl/DAV:ace' => array(3, null),
            '/DAV:acl/DAV:ace[1]/DAV:principal' => array(1, 1),
            '/DAV:acl/DAV:ace[1]/DAV:principal/DAV:property' => array(1, 1),
            '/DAV:acl/DAV:ace[1]/DAV:principal/DAV:property/DAV:owner' => array(1, 0),
            '/DAV:acl/DAV:ace[1]/DAV:grant' => array(1, 1),
            '/DAV:acl/DAV:ace[1]/DAV:grant/DAV:privilege' => array(1, 1),
            '/DAV:acl/DAV:ace[1]/DAV:grant/DAV:privilege/DAV:all' => array(1, 0),

            '/DAV:acl/DAV:ace[2]/DAV:principal' => array(1, 1),
            '/DAV:acl/DAV:ace[2]/DAV:principal/DAV:href' => array(1, 1),
            '/DAV:acl/DAV:ace[2]/DAV:grant' => array(1, 2),
            '/DAV:acl/DAV:ace[2]/DAV:grant/DAV:privilege' => array(2, null),
            '/DAV:acl/DAV:ace[2]/DAV:grant/DAV:privilege[1]' => array(1, 1),
            '/DAV:acl/DAV:ace[2]/DAV:grant/DAV:privilege[1]/DAV:read' => array(1, 0),
            '/DAV:acl/DAV:ace[2]/DAV:grant/DAV:privilege[2]' => array(1, 1),
            '/DAV:acl/DAV:ace[2]/DAV:grant/DAV:privilege[2]/DAV:write' => array(1, 0),

            '/DAV:acl/DAV:ace[3]/DAV:principal' => array(1, 1),
            '/DAV:acl/DAV:ace[3]/DAV:principal/DAV:authenticated' => array(1, 0),
            '/DAV:acl/DAV:ace[3]/DAV:grant' => array(1, 1),
            '/DAV:acl/DAV:ace[3]/DAV:grant/DAV:privilege[1]' => array(1, 1),
            '/DAV:acl/DAV:ace[3]/DAV:grant/DAV:privilege[1]/C:read-free-busy' => array(1, 0),
        );

        $this->checkXML($expected, $built_acl);
    }

    /**
     * Checks an XML against provided expected array
     *
     * @param mixed $expected
     * @param mixed $xml_text
     * @access private
     * @return void
     */
    private function checkXML($expected, $xml_text)
    {
        $parsed_xml = new \DOMDocument();
        $parsed_xml->loadXML($xml_text);

        $xpath = new \DOMXpath($parsed_xml);

        // Register some namespaces
        $xpath->registerNamespace('DAV', 'DAV:');
        $xpath->registerNamespace('C', 'urn:ietf:params:xml:ns:caldav');

        foreach ($expected as $path => $values) {
            list($occurrences, $children) = $values;
            $result = $xpath->query($path);
            $this->assertEquals(
                $occurrences,
                $result->length,
                'Expected ' . $occurrences . ' occurrences for ' . $path . ', found ' . $result->length
            );

            if ($occurrences == 1) {
                $found_children = $result->item(0)->childNodes->length;
                $this->assertEquals(
                    $children,
                    $found_children,
                    'Expected ' . $children . ' children for ' . $path . ', found ' . $found_children
                );
            }
        }
    }
}
