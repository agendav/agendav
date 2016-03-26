<?php
namespace AgenDAV\Http;

use AgenDAV\Http\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testAddAndSetHeaders()
    {
        $guzzle = new GuzzleClient();
        $client = new Client($guzzle);

        $this->assertNull($client->getHeader('Non-existing'));

        $client->setHeader('Test-Header', 'Test-Value');
        $this->assertEquals($client->getHeader('Test-Header'), 'Test-Value');

        // Overwrite old header
        $client->setHeader('Test-Header', 'Second value');
        $this->assertEquals($client->getHeader('Test-Header'), 'Second value');

        // Add a value to the header
        $client->addHeader('Test-Header', 'Additional value');
        $this->assertEquals(
            $client->getHeader('Test-Header'),
            array('Second value', 'Additional value')
        );

        // Add one more value to the header
        $client->addHeader('Test-Header', 'One more additional value');
        $this->assertEquals(
            $client->getHeader('Test-Header'),
            array('Second value', 'Additional value', 'One more additional value')
        );

        // addHeader for non-defined header
        $client->addHeader('New-Header', 'new');
        $this->assertEquals($client->getHeader('New-Header'), 'new');
    }


    public function testHeadersSent()
    {
        $client = $this->createClient([
            new Response(202)
        ]);

        $client->setHeader('Test-Header', 'Test value');
        $client->request('GET', '/');

        $last_request = $client->getLastRequest();
        $this->assertEquals(
            'Test value',
            $last_request->getHeaderLine('Test-Header')
        );
    }

    /**
     * Should throw an exception
     * @expectedException AgenDAV\Exception\NotAuthenticated
     */
    public function test401()
    {
        $client = $this->createClient([
            new Response(401)
        ]);

        $client->request('GET', '/');
    }

    /**
     * Should throw an exception
     * @expectedException AgenDAV\Exception\PermissionDenied
     */
    public function test403()
    {
        $client = $this->createClient([
            new Response(403)
        ]);

        $client->request('GET', '/');
    }
    /**
     * Should throw an exception
     * @expectedException AgenDAV\Exception\NotFound
     */
    public function test404()
    {
        $client = $this->createClient([
            new Response(404)
        ]);

        $client->request('GET', '/');
    }
    /**
     * Should throw an exception
     * @expectedException AgenDAV\Exception\ElementModified
     */
    public function test412()
    {
        $client = $this->createClient([
            new Response(412)
        ]);

        $client->request('GET', '/');
    }

    public function testCleanHeadersAfterARequest()
    {
        $client = $this->createClient([
            new Response(200),
            new Response(200),
        ]);

        $client->setHeader('Test-Header', 'Value');
        $client->request('GET', '/');

        // Send a second request, does it contain the Test-Header header?
        $client->request('GET', '/');

        $headers = $client->getLastRequest()->getHeaders();
        $this->assertFalse(
            isset($headers['Test-Header']),
            'Headers are not cleaned after a request'
        );
    }

    protected function createClient(array $mocked_responses)
    {
        $mock = new MockHandler($mocked_responses);
        $handler_stack = HandlerStack::create($mock);

        $guzzle = new GuzzleClient(['handler' => $handler_stack]);

        return new Client($guzzle);
    }
}
