<?php
namespace AgenDAV\Http;

use AgenDAV\Http\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;

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
        $guzzle = new GuzzleClient();
        $response_mock = new Mock(array(
            'HTTP/1.1 202 OKr\nContent-Length: 0\r\n\r\n'
        ));
        $guzzle->getEmitter()->attach($response_mock);

        $client = new Client($guzzle);
        $client->setHeader('Test-Header', 'Test value');
        $client->request('GET', '/');

        $headers = $client->getLastRequest()->getHeaders();
        $this->assertArrayHasKey('Test-Header', $headers);
        $this->assertEquals($headers['Test-Header'], array('Test value'));
    }

    /**
     * Should not throw any exceptions at all
     */
    public function testNoExceptions()
    {
        $guzzle = new GuzzleClient();
        $response_mock = new Mock(array(
            'HTTP/1.1 404 Not foundr\nContent-Length: 0\r\n\r\n'
        ));
        $guzzle->getEmitter()->attach($response_mock);

        $client = new Client($guzzle);
        $client->request('GET', '/');
    }

    public function testCleanHeadersAfterARequest()
    {
        $guzzle = new GuzzleClient();
        $response_mock = new Mock(array(
            'HTTP/1.1 200 OK\nContent-Length: 0\r\n\r\n',
            'HTTP/1.1 200 OK\nContent-Length: 0\r\n\r\n',
        ));
        $guzzle->getEmitter()->attach($response_mock);

        $client = new Client($guzzle);
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
}
