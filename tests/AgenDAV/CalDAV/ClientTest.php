<?php

namespace AgenDAV\CalDAV;

use Mockery as m;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use AgenDAV\Http\Client as HttpClient;
use AgenDAV\XML\Generator;
use AgenDAV\XML\Parser;

/**
 * @author jorge
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var \AgenDAV\Http\Client */
    protected $http_client;

    /** @var \AgenDAV\XML\Generator */
    protected $xml_generator;

    /** @var \AgenDAV\XML\Parser */
    protected $xml_parser;

    public function setUp()
    {
        $this->http_client = m::mock('AgenDAV\Http\Client');
        $this->xml_generator = m::mock('AgenDAV\XML\Generator');
        $this->xml_parser = m::mock('AgenDAV\XML\Parser');
    }

    public function testCantAuthenticate()
    {
        // #1 Test an authentication failure
        $this->http_client->shouldReceive('request')
            ->with('OPTIONS', '')
            ->andThrow(m::mock('\GuzzleHttp\Exception\ClientException'))
            ->once();
        $caldav_client = $this->createCalDAVClient();

        $this->assertFalse($caldav_client->canAuthenticate(), 'canAuthenticate() works on 4xx/5xx');
    }

    public function testCantAuthenticateNotCalDAV()
    {
        // #2 Test a non CalDAV server
        $response = new Response(200, []);
        $this->http_client->shouldReceive('request')
            ->with('OPTIONS', '')
            ->andReturn($response)
            ->once();
        $caldav_client = $this->createCalDAVClient();
        $this->assertFalse($caldav_client->canAuthenticate(), 'canAuthenticate() works on non CalDAV servers');
    }

    public function testCanAuthenticate()
    {
        // #3 Test a valid authentication
        $response = new Response(200, ['DAV' => '1, 3, extended-mkcol, access-control, calendarserver-principal-property-search, calendar-access, calendar-proxy']);
        $this->http_client->shouldReceive('request')
            ->with('OPTIONS', '')
            ->andReturn($response)
            ->once();
        $caldav_client = $this->createCalDAVClient();
        $this->assertTrue(
            $caldav_client->canAuthenticate(),
            'canAuthenticate() does not work when authentication is successful on a CalDAV server'
        );
    }


    /** @expectedException \UnexpectedValueException */
    public function testGetCurrentUserPrincipalNotFound()
    {
        $this->xml_generator->shouldReceive('propfindBody')
            ->andReturn('returned_body_from_xml_generator')
            ->once();
        $response = new Response(207, [], Stream::factory('fake_response'));
        $this->http_client->shouldReceive('setHeader')
            ->with('Depth', 0)
            ->once()
            ->ordered();
        $this->http_client->shouldReceive('setContentTypeXML')
            ->once()
            ->ordered();
        $this->http_client->shouldReceive('request')
            ->with('PROPFIND', '', 'returned_body_from_xml_generator')
            ->andReturn($response)
            ->once()
            ->ordered();
        $this->xml_parser->shouldReceive('extractPropertiesFromMultistatus')
            ->with('fake_response', true)
            ->andReturn([])
            ->once();

        $caldav_client = $this->createCalDAVClient();

        $caldav_client->getCurrentUserPrincipal();
    }

    public function testGetCurrentUserPrincipalSuccess()
    {
        $this->xml_generator->shouldReceive('propfindBody')
            ->andReturn('returned_body_from_xml_generator')
            ->once();
        $response = new Response(207, [], Stream::factory('fake_response'));
        $this->http_client->shouldReceive('setHeader')
            ->with('Depth', 0)
            ->once()
            ->ordered();
        $this->http_client->shouldReceive('setContentTypeXML')
            ->once()
            ->ordered();
        $this->http_client->shouldReceive('request')
            ->with('PROPFIND', '', 'returned_body_from_xml_generator')
            ->andReturn($response)
            ->once()
            ->ordered();
        $this->xml_parser->shouldReceive('extractPropertiesFromMultistatus')
            ->with('fake_response', true)
            ->andReturn(['/principals/fake'])
            ->once();

        $caldav_client = $this->createCalDAVClient();

        $this->assertEquals(
            '/principals/fake',
            $caldav_client->getCurrentUserPrincipal()
        );
    }


    /**
     * Create CalDAV client using other mocks
     */
    protected function createCalDAVClient()
    {
        return new Client($this->http_client, $this->xml_generator, $this->xml_parser);
    }
}
