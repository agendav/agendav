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

    public function search(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $filter = $request->getQueryParams()['term'] ?? null;
        if ($filter === null) {
            $response->getBody()->write('[]');
            return $response->withHeader('Content-Type', 'application/json');
        }

        $result = $this->container->get('principals.repository')->search($filter);

        $fractal = $this->container->get('fractal');
        $fractal->setSerializer(new PlainSerializer());
        $transformer = new PrincipalTransformer();
        $collection = new Collection($result, $transformer);

        $response->getBody()->write((string) json_encode($fractal->createData($collection)->toArray()));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
