<?php

namespace AgenDAV\Session;

use AgenDAV\Encrypt\Encryptor;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;
use \Mockery as m;

class SessionEncrypterTest extends \PHPUnit_Framework_TestCase
{
    public function testSessionGetsEncrypted()
    {

        $mocked_encryptor = m::mock('AgenDAV\Encrypt\Encryptor')
            ->shouldReceive('encrypt')
            ->with('test data')
            ->andReturn('encrypted data')
            ->once()
            ->getMock();

        $mocked_handler = m::mock('\SessionHandlerInterface')
            ->shouldReceive('write')
            ->withArgs([0, 'encrypted data'])
            ->andReturn(true)
            ->once()
            ->getMock();

        $session_encrypter = new SessionEncrypter(
            $mocked_handler,
            $mocked_encryptor
        );

        $this->assertEquals(
            true,
            $session_encrypter->write(0, 'test data')
        );
    }

    public function testSessionGetsDecrypted()
    {

        $mocked_encryptor = m::mock('AgenDAV\Encrypt\Encryptor')
            ->shouldReceive('decrypt')
            ->with('encrypted data')
            ->andReturn('test data')
            ->once()
            ->getMock();

        $mocked_handler = m::mock('\SessionHandlerInterface')
            ->shouldReceive('read')
            ->with(0)
            ->andReturn('encrypted data')
            ->once()
            ->getMock();

        $session_decrypter = new SessionEncrypter(
            $mocked_handler,
            $mocked_encryptor
        );

        $this->assertEquals(
            'test data',
            $session_decrypter->read(0)
        );
    }
}
