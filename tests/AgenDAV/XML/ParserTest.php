<?php
namespace AgenDAV\XML;

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
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
        $this->assertArrayHasKey(
            '/cal.php/',
            $result,
            'parseMultistatus returned structure with unexpected root node'
        );

        $this->assertArrayHasKey(
            '{DAV:}current-user-principal',
            $result['/cal.php/'],
            'parseMultistatus did not detect {DAV:}current-user-principal'
        );

        $this->assertEquals(
            '/cal.php/principals/demo/',
            $result['/cal.php/']['{DAV:}current-user-principal']->getHref(),
            'parseMultistatus could not read the href value from current-user-principal'
        );

        // Test with $single_element enabled
        $result = $parser->extractPropertiesFromMultistatus($body, true);

        $this->assertArrayHasKey(
            '{DAV:}current-user-principal',
            $result,
            'parseMultistatus for single element mode did not return {DAV:}current-user-principal'
        );

        $user_principal = $result['{DAV:}current-user-principal'];

        $this->assertInstanceOf(
            '\\Sabre\\DAV\\Xml\\Property\\Href',
            $user_principal,
            'current-user-principal is not resolved to the Href class!'
        );

        $this->assertEquals(
            '/cal.php/principals/demo/',
            $user_principal->getHref(),
            'Single element on extractPropertiesFromMultistatus is not working'
        );
    }

    /**
     * @expectedException \Sabre\Xml\LibXMLException
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
            '\Sabre\DAV\Xml\Property\ResourceType',
            $result['/cal.php/calendars/demo/default/']['{DAV:}resourcetype']
        );
    }

}
