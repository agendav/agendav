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

use AgenDAV\UserContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JavaScriptCode
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function settingsAction(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $site_config = $this->getSiteConfig($request);
        $preferences = $this->getPreferences();

        $body = $this->container->get('twig')->render('jsconfig.html', [
            'site_config' => $site_config,
            'preferences' => $preferences,
        ]);

        $response->getBody()->write($body);
        return $response
            ->withHeader('Content-Type', 'text/javascript')
            ->withHeader('Cache-Control', 'private, must-revalidate')
            ->withHeader('Expires', gmdate('D, d M Y H:i:s', time()) . ' GMT')
            ->withHeader('Pragma', 'no-cache');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSiteConfig(ServerRequestInterface $request): array
    {
        $serverParams = $request->getServerParams();
        $scriptName = $serverParams['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(dirname($scriptName), '/\\');

        $settings = [
            'base_url' => $basePath,
            'base_app_url' => $basePath . '/',
            'agendav_version' => \AgenDAV\Version::V,
            'enable_calendar_sharing' => $this->container->get('calendar.sharing'),
            'calendar_colors' => $this->container->get('calendar.colors'),
            'default_calendar_color' => '#' . $this->container->get('calendar.colors')[0],
            'show_public_caldav_url' => $this->container->get('caldav.publicurls'),
        ];

        if ($this->container->get('caldav.publicurls')) {
            $settings['caldav_public_base_url'] = $this->container->get('caldav.baseurl.public');
        }

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPreferences(): array
    {
        return $this->container->get(UserContext::class)->getPreferences()->getAll();
    }
}
