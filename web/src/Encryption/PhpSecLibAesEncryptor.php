<?php

/*
 * Copyright 2015 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AgenDAV\Encryption;

use AgenDAV\Encryption\Encryptor;
use phpseclib\Crypt\AES;

class PhpSecLibAesEncryptor implements Encryptor
{
    /** @var phpseclib\Crypt\AES */
    private $encryptor;

    /**
     * Builds a new Encryptor based on phpseclib/phpseclib
     *
     * @param AesEncryptor $encryptor
     * @param string $key
     */
    public function __construct(AES $encryptor, $key)
    {
        $this->encryptor = $encryptor;
        $this->encryptor->setKey($key);
    }

    /**
     * @param string $data  String to encrypt
     * @return string encrypted string
     */
    public function encrypt($data)
    {
        return $this->encryptor->encrypt($data);
    }

    /**
     * @param string $data  String to decrypt
     * @return string decrypted string
     */
    public function decrypt($data)
    {
        if (empty($data)) {
            return $data;
        }

        return $this->encryptor->decrypt($data);
    }
}
