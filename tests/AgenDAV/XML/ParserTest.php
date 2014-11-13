<?php
namespace AgenDAV\XML;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testExtractPropertiesFromMultistatus()
    {
        $body = <<<EOBODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">
    <d:response>
        <d:href>/cal.php/</d:href>
        <d:propstat>
            <d:prop>
                <d:current-user-principal>
                    <d:href>/cal.php/principals/demo/</d:href>
                </d:current-user-principal>
            </d:prop>
            <d:status>HTTP/1.1 200 OK</d:status>
        </d:propstat>
        <d:propstat>
            <d:prop>
                <d:notfound />
                <d:alsonotfound />
            </d:prop>
            <d:status>HTTP/1.1 404 Not Found</d:status>
        </d:propstat>
    </d:response>
</d:multistatus>
EOBODY;

        $parser = new Parser();

        $result = $parser->extractPropertiesFromMultistatus($body);
        $this->assertEquals(
            $result,
            [
                '/cal.php/' => [
                    '{DAV:}current-user-principal' => '/cal.php/principals/demo/',
                ],
            ]
        );

        // Test with $single_element enabled
        $result = $parser->extractPropertiesFromMultistatus($body, true);
        $this->assertEquals(
            $result,
            [ '{DAV:}current-user-principal' => '/cal.php/principals/demo/' ],
            'Single element on extractPropertiesFromMultistatus is not working'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */

    public function testInvalidXML()
    {
        $parser = new Parser();
        $parser->extractPropertiesFromMultistatus('this is clearly not an xml document');
    }


    /*
     * Tests that <resourcetype> get converted into a Sabre\DAV\Property\ResourceType
     * object
     */
    public function testResourceTypeClass()
    {
        $body = <<<EOBODY
<?xml version="1.0" encoding="utf-8"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">
<d:response>
    <d:href>/cal.php/calendars/demo/default/</d:href>
    <d:propstat>
        <d:prop>
            <d:displayname>Default calendar</d:displayname>
            <d:resourcetype>
                <d:collection/>
                <cal:calendar/>
            </d:resourcetype>
        </d:prop>
        <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
</d:response>
</d:multistatus>
EOBODY;

        $parser = new Parser();
        $result = $parser->extractPropertiesFromMultistatus($body);

        $this->assertInstanceOf(
            '\Sabre\DAV\Property\ResourceType',
            $result['/cal.php/calendars/demo/default/']['{DAV:}resourcetype']
        );
    }

}
