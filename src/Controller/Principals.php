<?php

namespace AgenDAV\Controller;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\Data\Transformer\PrincipalTransformer;
use AgenDAV\Data\Serializer\PlainSerializer;
use League\Fractal\Resource\Collection;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Principals
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * Defensive cap on the search term — long inputs would still be forwarded
     * to the CalDAV server, which becomes a DoS amplifier for clients that can
     * send a 1KB query but make the server scan a much larger principal set.
     * Real autocompletion queries are short.
     */
    private const MAX_TERM_LENGTH = 64;

    public function search(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $filter = $request->getQueryParams()['term'] ?? null;
        if ($filter === null || $filter === '') {
            $response->getBody()->write('[]');
            return $response->withHeader('Content-Type', 'application/json');
        }

        $filter = mb_substr((string) $filter, 0, self::MAX_TERM_LENGTH);

        $result = $this->container->get('principals.repository')->search($filter);

        $fractal = $this->container->get('fractal');
        $fractal->setSerializer(new PlainSerializer());
        $transformer = new PrincipalTransformer();
        $collection = new Collection($result, $transformer);

        $response->getBody()->write((string) json_encode($fractal->createData($collection)->toArray()));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
