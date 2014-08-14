<?php

namespace AgenDAV\Encryption;

use \Mockery as m;

/*
 * All these tests will show a warning:
 * PHP Warning:  mcrypt_module_close() expects parameter 1 to be resource, null given
 *
 * Can't get it sorted out, seems that Mockery doesn't support overriding the
 * __destruct() method
 */

class KeboolaAesEncryptorTest extends \PHPUnit_Framework_TestCase
{
    public function testEncrypt()
    {
        $internal_encrytor = m::mock('\Keboola\Encryption\AesEncryptor')
            ->shouldReceive('encrypt')
            ->with('TEST')->andReturn('ENCRYPTED_TEST')->once()
            ->getMock();

        $encryptor = new KeboolaAesEncryptor($internal_encrytor);
        $this->assertEquals($encryptor->encrypt('TEST'), 'ENCRYPTED_TEST');
    }

    public function testDecrypt()
    {
        $internal_encrytor = m::mock('\Keboola\Encryption\AesEncryptor')
            ->shouldReceive('decrypt')->once()
            ->with('ENCRYPTED_TEST')->andReturn('TEST');

        $internal_encrytor = $internal_encrytor->getMock();

        $encryptor = new KeboolaAesEncryptor($internal_encrytor);
        $this->assertEquals($encryptor->decrypt('ENCRYPTED_TEST'), 'TEST');
        $this->assertEquals($encryptor->decrypt(''), '');
    }
}
