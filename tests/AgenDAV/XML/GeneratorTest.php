<?php
namespace AgenDAV\XML;

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\CalDAV\Share\Permissions;
use AgenDAV\CalDAV\Share\ACL;
use Sabre\Xml\Reader;
use \Mockery as m;

/**
 * @author jorge
 */
class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testPropfindBody()
    {
        $generator = $this->createXMLGenerator();

        $body = trim($generator->propfindBody(array(
            '{DAV:}resourcetype',
            '{urn:ietf:params:xml:ns:caldav}calendar-home-set',
            '{http://apple.com/ns/ical/}calendar-color',
            '{http://fake.namespace.org}calendar-color'
        )));

        $expected = array (
          'name' => '{DAV:}propfind',
          'value' =>
          array (
            0 =>
            array (
              'name' => '{DAV:}prop',
              'value' =>
              array (
                0 =>
                array (
                  'name' => '{DAV:}resourcetype',
                  'value' => NULL,
                  'attributes' => array (),
                ),
                1 =>
                array (
                  'name' => '{urn:ietf:params:xml:ns:caldav}calendar-home-set',
                  'value' => NULL,
                  'attributes' => array (),
                ),
                2 =>
                array (
                  'name' => '{http://apple.com/ns/ical/}calendar-color',
                  'value' => NULL,
                  'attributes' => array (),
                ),
                3 =>
                array (
                  'name' => '{http://fake.namespace.org}calendar-color',
                  'value' => NULL,
                  'attributes' => array (),
                ),
              ),
              'attributes' => array (),
            ),
          ),
          'attributes' => array (),
        );
        $reader = new Reader();
        $reader->xml($body);
        $this->assertEquals($expected, $reader->parse());
    }

    public function testMkCalendarBody()
    {
        $generator = $this->createXMLGenerator();

        $properties = [
            Calendar::DISPLAYNAME => 'Calendar name',
            '{urn:fake}attr' => 'value',
        ];

        $body = $generator->mkCalendarBody($properties);

        $expected = array (
          'name' => '{urn:ietf:params:xml:ns:caldav}mkcalendar',
          'value' =>
          array (
            0 =>
            array (
              'name' => '{DAV:}set',
              'value' =>
              array (
                0 =>
                array (
                  'name' => '{DAV:}prop',
                  'value' =>
                  array (
                    0 =>
                    array (
                      'name' => '{DAV:}displayname',
                      'value' => 'Calendar name',
                      'attributes' => array (),
                    ),
                    1 =>
                    array (
                      'name' => '{urn:fake}attr',
                      'value' => 'value',
                      'attributes' => array (),
                    ),
                  ),
                  'attributes' => array (),
                ),
              ),
              'attributes' => array (),
            ),
          ),
          'attributes' => array (),
        );

        $reader = new Reader();
        $reader->xml($body);

        $this->assertEquals($expected, $reader->parse());
    }

    /**
     * Make sure that the body doesn't contain a <set><prop></prop></set> group
     */
    public function testMkCalendarBodyWithoutProperties()
    {
        $generator = $this->createXMLGenerator();

        $body = $generator->mkCalendarBody([]);

        $expected = array (
          'name' => '{urn:ietf:params:xml:ns:caldav}mkcalendar',
          'value' => null,
          'attributes' => array (),
        );
        $reader = new Reader();
        $reader->xml($body);

        $this->assertEquals($expected, $reader->parse());
    }

    public function testproppatchBody()
    {
        $generator = $this->createXMLGenerator();

        $properties = [
            Calendar::DISPLAYNAME => 'Calendar name',
            Calendar::COLOR => '#f0f0f0aa',
            '{urn:fake}attr' => 'value',
        ];

        $body = $generator->proppatchBody($properties);

        $expected = array (
          'name' => '{DAV:}propertyupdate',
          'value' =>
          array (
            0 =>
            array (
              'name' => '{DAV:}set',
              'value' =>
              array (
                0 =>
                array (
                  'name' => '{DAV:}prop',
                  'value' =>
                  array (
                    0 =>
                    array (
                      'name' => '{DAV:}displayname',
                      'value' => 'Calendar name',
                      'attributes' => array (),
                    ),
                    1 =>
                    array (
                      'name' => '{http://apple.com/ns/ical/}calendar-color',
                      'value' => '#f0f0f0aa',
                      'attributes' => array (),
                    ),
                    2 =>
                    array (
                      'name' => '{urn:fake}attr',
                      'value' => 'value',
                      'attributes' => array (),
                    ),
                  ),
                  'attributes' => array (),
                ),
              ),
              'attributes' => array (),
            ),
          ),
          'attributes' => array (),
        );

        $reader = new Reader();
        $reader->xml($body);

        $this->assertEquals($expected, $reader->parse());
    }

    public function testEventsReportBody()
    {
        $generator = $this->createXMLGenerator();
        $test_filter = new \AgenDAV\CalDAV\Filter\Test('{http://fake.com/}test');

        $body = $generator->calendarQueryBody($test_filter);

        $expected = <<<EOXML
<?xml version="1.0" encoding="UTF-8"?>
<C:calendar-query xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" xmlns:A="http://apple.com/ns/ical/">
    <d:prop>
        <d:getetag/>
        <C:calendar-data/>
    </d:prop>
    <C:filter>
        <C:comp-filter name="VCALENDAR">
            <C:comp-filter name="VEVENT">
                <x1:test xmlns:x1="http://fake.com/"/>
            </C:comp-filter>
        </C:comp-filter>
    </C:filter>
</C:calendar-query>
EOXML;

        $this->assertXmlStringEqualsXmlString($expected, $body);
    }


    public function testACLGenerator()
    {
        $permissions = new permissions([
            'owner' => [ '{DAV:}all', '{urn:he:man}master-of-universe' ],
            'default' => [ '{urn:ietf:params:xml:ns:caldav}read-free-busy' ],
            'read-write' => [ '{DAV:}write' ],
            'read-only' => [ '{DAV:}read' ],
        ]);

        $acl = new ACL($permissions);
        $acl->addGrant('/jorge', 'read-write');
        $acl->addGrant('/rigodon', 'read-only');

        $generator = $this->createXMLGenerator();
        $generated_acl = $generator->aclBody($acl);

        $expected_acl = <<<ACLBODY
<?xml version="1.0" encoding="UTF-8"?>
<d:acl xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" xmlns:x0="urn:he:man">
  <d:ace>
    <d:principal>
      <d:property>
        <d:owner/>
      </d:property>
    </d:principal>
    <d:grant>
      <d:all/>
      <x0:master-of-universe/>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:authenticated/>
    </d:principal>
    <d:grant>
      <C:read-free-busy/>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/jorge</d:href>
    </d:principal>
    <d:grant>
      <d:write/>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/rigodon</d:href>
    </d:principal>
    <d:grant>
      <d:read/>
    </d:grant>
  </d:ace>
</d:acl>
ACLBODY;

        $this->assertXmlStringEqualsXmlString($expected_acl, $generated_acl);

    }

    public function testPrincipalPropertySearchBody()
    {
      $filter = new \AgenDAV\CalDAV\Filter\PrincipalPropertySearch('example');
      $generator = $this->createXMLGenerator();
      $body = $generator->principalPropertySearchBody($filter);
      $expected_body = <<<BODY
<d:principal-property-search xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:" test="anyof">
  <d:property-search>
    <d:prop>
      <C:calendar-user-address-set/>
    </d:prop>
    <d:match>example</d:match>
  </d:property-search>
  <d:property-search>
    <d:prop>
      <d:displayname/>
    </d:prop>
    <d:match>example</d:match>
  </d:property-search>
  <d:prop>
    <d:displayname/>
    <d:email/>
  </d:prop>
</d:principal-property-search>
BODY;

      $this->assertXmlStringEqualsXmlString($expected_body, $body);
    }

    /**
     * Create a new XMLGenerator without output formatting
     **/
    public function createXMLGenerator()
    {
        return new Generator(false);
    }
}
