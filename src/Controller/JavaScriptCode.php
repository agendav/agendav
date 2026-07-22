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

use AgenDAV\AutologinState;
use AgenDAV\UserContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteParserInterface;

class JavaScriptCode
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
    * Single public entry point for routes.php to fetch config settings and
    * render them as inline JSON afterwards.
    */
    public function renderConfig(ServerRequestInterface $request): string
    {
        return $this->container->get('twig')->render('config-data.html', [
            'site_config' => $this->getSiteConfig($request),
            'preferences' => $this->getPreferences(),
        ]);
    }

    /**
    * @return array<string, mixed>
    */
    protected function getSiteConfig(ServerRequestInterface $request): array
    {
        // Derive base URLs from Slim's RouteParser rather than $_SERVER['SCRIPT_NAME'].
        // Behind a misconfigured reverse proxy SCRIPT_NAME can be attacker-
        // controlled (X-Original-URL, X-Rewrite-URL, path traversal); urlFor
        // honours the App's configured basePath, which is set explicitly in
        // index.php and not derived from request headers.
        /** @var RouteParserInterface $routeParser */
        $routeParser = $this->container->get(RouteParserInterface::class);
        $appUrl = $routeParser->urlFor('calendar');
        $baseUrl = rtrim($appUrl, '/');

        $settings = [
            'base_url' => $baseUrl,
            'base_app_url' => $appUrl,
            'agendav_version' => \AgenDAV\Version::V,
            'enable_calendar_sharing' => $this->container->get('calendar.sharing'),
            'enable_calendar_subscriptions' => $this->container->get('calendar.subscriptions'),
            'calendar_colors' => array_map(
                fn ($c) => '#' . ltrim($c, '#'),
                $this->container->get('calendar.colors')
            ),
            'default_calendar_color' => '#' . ltrim($this->container->get('calendar.colors')[0], '#'),
            'show_public_caldav_url' => $this->container->get('caldav.publicurls'),
            'autologin_read_only' => $this->container->get(AutologinState::class)->isReadOnly(),
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
