<?php

namespace AgenDAV\CalDAV;

use GuzzleHttp\Client as GuzzleHttp;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Message\Response;
use AgenDAV\Http\Client as HttpClient;
use AgenDAV\XML\Generator;
use AgenDAV\XML\Parser;

/**
 * @author jorge
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var GuzzleHttp\Subscriber\History */
    protected $history;

    protected $http_client;

    public function testCanAuthenticate()
    {
        $responses = [];

        // #1 Test an authentication failure
        $responses[] = new Response(401);

        // #2 Test a non CalDAV server
        $responses[] = new Response(200, []);

        // #3 Test a valid authentication
        $responses[] = new Response(200, ['DAV' => '1, 3, extended-mkcol, access-control, calendarserver-principal-property-search, calendar-access, calendar-proxy']);

        $caldav_client = $this->getMockedClient($responses);

        // #1
        $this->assertFalse($caldav_client->canAuthenticate(), 'canAuthenticate() works on 401');
        // #2
        $this->assertFalse($caldav_client->canAuthenticate(), 'canAuthenticate() works on non CalDAV servers');
        // #3
        $this->assertTrue(
            $caldav_client->canAuthenticate(),
            'canAuthenticate() does not work when authentication is successful on a CalDAV server'
        );
    }


    protected function getMockedClient(Array $responses)
    {
        $guzzle = new GuzzleHttp();
        $mock = new Mock($responses);
        $guzzle->getEmitter()->attach($mock);

        $this->history = new History();
        $guzzle->getEmitter()->attach($this->history);

        $this->http_client = new HttpClient($guzzle);

        return new Client(
            $this->http_client,
            new Generator(),
            new Parser()
        );
    }
}
