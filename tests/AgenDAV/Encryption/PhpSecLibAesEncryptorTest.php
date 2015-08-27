<?php

namespace AgenDAV\Encryption;

use \Mockery as m;
use phpseclib\Crypt\AES;

class PhpSecLibAesEncryptorTest extends \PHPUnit_Framework_TestCase
{

    private $aes;

    private $key;

    public function setUp()
    {
        $this->aes = new AES();
        $this->key = 'test_key_for_aes';
    }
    public function testEncryptAndDecrypt()
    {
        $encryptor = new PhpSecLibAesEncryptor($this->aes, $this->key);

        $encrypted = $encryptor->encrypt('TEST');

        $this->assertEquals('TEST', $encryptor->decrypt($encrypted));
    }

    public function testDecryptEmptyString()
    {
        $encryptor = new PhpSecLibAesEncryptor($this->aes, $this->key);

        $this->assertEquals($encryptor->decrypt(''), '');
    }
}
