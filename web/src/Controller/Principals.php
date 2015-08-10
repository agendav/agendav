<?php
namespace AgenDAV\Controller;

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

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AgenDAV\Data\Transformer\PrincipalTransformer;
use AgenDAV\Data\Serializer\PlainSerializer;
use League\Fractal\Resource\Collection;

/**
 * Principals controller. Used to search principals by username or email
 */
class Principals
{
    public function search(Request $request, Application $app)
    {
        $principals_repository = $app['principals.repository'];

        $filter = $request->query->get('term');
        if ($filter === null) {
            return new JsonResponse([]);
        }

        $result = $principals_repository->search($filter);

        $fractal = $app['fractal'];
        $fractal->setSerializer(new PlainSerializer);
        $transformer = new PrincipalTransformer();
        $collection = new Collection($result, $transformer);

        return new JsonResponse($fractal->createData($collection)->toArray());
    }
}
