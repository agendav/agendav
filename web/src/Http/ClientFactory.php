<?php
namespace AgenDAV\Http;

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

use GuzzleHttp\Client as GuzzleClient;
use AgenDAV\Http\Client;;
use Symfony\Component\HttpFoundation\Session\Session;

class ClientFactory
{
    public static function create(GuzzleClient $guzzle, Session $session, $auth_type)
    {
        $client = new Client($guzzle);
        if ($session->has('username') && $session->has('password')) {
            $client->setAuthentication(
                $session->get('username'),
                $session->get('password'),
                $auth_type
            );
        }

        return $client;
    }
}
