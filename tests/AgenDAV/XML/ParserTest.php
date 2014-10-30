<?php
namespace AgenDAV\XML;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParseMultiStatus()
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
    </d:response>
</d:multistatus>
EOBODY;

        $parser = new Parser();

        $result = $parser->parseMultiStatus($body);
        $this->assertEquals(
            $result,
            [
                '/cal.php/' => [
                    200 => [
                        '{DAV:}current-user-principal' => '/cal.php/principals/demo/',
                    ],
                ],
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     */

    public function testInvalidXML()
    {
        $parser = new Parser();
        $parser->parseMultiStatus('this is clearly not an xml document');
    }
}
