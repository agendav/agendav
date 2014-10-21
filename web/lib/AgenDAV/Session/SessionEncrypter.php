<?php

/*
 * Copyright 2014 Jorge López Pérez <jorge@adobo.org>
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

namespace AgenDAV\Session;

use Symfony\Component\HttpFoundation\Session\Storage\Proxy\SessionHandlerProxy;
use AgenDAV\Encryption\Encryptor;

/**
 * This proxy just encrypts session data using the strategy shown at
 * http://symfony.com/doc/current/cookbook/session/proxy_examples.html#encryption-of-session-data
 */

class SessionEncrypter extends SessionHandlerProxy
{
    /**
     * @var Encryptor $encryptor
     */
    private $encryptor;

    /**
     * @param mixed $encryptor
     */
    public function __construct(\SessionHandlerInterface $handler, $encryptor)
    {
        $this->encryptor = $encryptor;

        parent::__construct($handler);
    }

    public function read($id)
    {
        $data = parent::read($id);

        return $this->encryptor->decrypt($data);
    }

    public function write($id, $data)
    {
        $data = $this->encryptor->encrypt($data);

        return parent::write($id, $data);
    }
}
