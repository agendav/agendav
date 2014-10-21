<?php

namespace AgenDAV\Encryption;

use \Mockery as m;

class KeboolaAesEncryptorTest extends \PHPUnit_Framework_TestCase
{
    public function testEncrypt()
    {
        $internal_encrytor = m::mock('AgenDAV\Encryption\FakeAesEncryptor')
            ->shouldReceive('encrypt')
            ->with('TEST')->andReturn('ENCRYPTED_TEST')->once()
            ->getMock();

        $encryptor = new KeboolaAesEncryptor($internal_encrytor);
        $this->assertEquals($encryptor->encrypt('TEST'), 'ENCRYPTED_TEST');
    }

    public function testDecrypt()
    {
        $internal_encrytor = m::mock('AgenDAV\Encryption\FakeAesEncryptor')
            ->shouldReceive('decrypt')->once()
            ->with('ENCRYPTED_TEST')->andReturn('TEST');

        $internal_encrytor = $internal_encrytor->getMock();

        $encryptor = new KeboolaAesEncryptor($internal_encrytor);
        $this->assertEquals($encryptor->decrypt('ENCRYPTED_TEST'), 'TEST');
        $this->assertEquals($encryptor->decrypt(''), '');
    }
}
