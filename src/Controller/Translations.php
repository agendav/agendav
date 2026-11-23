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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /translations/{language} - UI messages of one language. Messages
 * missing in that language are returned in English. Unknown languages
 * return 404.
 *
 * The messages are the same for every user, so no login is required and
 * the response is cached by the browser. The frontend adds the AgenDAV
 * version as query parameter to load new messages after an update. During
 * development, use a hard reload or disable the browser cache.
 */
class Translations
{
    private const CACHE_FOREVER = 'public, max-age=31536000, immutable';

    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param string $language Route parameter, passed by name by the PHP-DI Slim bridge
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, string $language): ResponseInterface
    {
        $available = array_keys($this->container->get('languages'));
        if (!in_array($language, $available, true)) {
            return $response->withStatus(404);
        }

        $translator = $this->container->get('translator');
        $translations = array_merge(
            $translator->getCatalogue('en')->all('messages'),
            $translator->getCatalogue($language)->all('messages')
        );

        $response->getBody()->write((string) json_encode($translations));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', self::CACHE_FOREVER);
    }
}
