<?php

namespace AgenDAV\Encryption;

use \Keboola\Encryption\AesEncryptor;

/*
 * AesEncryptor __destruct() method calls to mcrypt_module_close() which
 * raises a PHP warning on tests because our mock is not complete.
 *
 * We sort it out by creating a fake class that just redefines the __destruct()
 * method.
 */

class FakeAesEncryptor extends AesEncryptor
{
    public function __destruct()
    {
    }
}

