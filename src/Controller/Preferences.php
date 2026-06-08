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

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\DateHelper;
use AgenDAV\UserContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Interfaces\RouteParserInterface;

class Preferences
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function indexAction(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $preferences = $this->container->get(UserContext::class)->getPreferences();

        /** @var Calendar[] $calendars */
        $calendars = $this->container->get('calendar.finder')->getCalendars();

        $calendars_as_options = [];
        foreach ($calendars as $calendar) {
            $key = $calendar->getUrl();
            $calendars_as_options[$key] = $calendar->getProperty(Calendar::DISPLAYNAME);
        }

        $body = $this->container->get('twig')->render(
            'preferences.html',
            [
                'scripts' => [],
                'available_timezones' => DateHelper::getAllTimeZones(),
                'available_languages' => $this->container->get('languages'),
                'timezone' => $preferences->get('timezone'),
                'calendars' => $calendars_as_options,
                'default_calendar' => $preferences->get('default_calendar'),
                'language' => $preferences->get('language'),
                'time_format' => $preferences->get('time_format'),
                'date_format' => $preferences->get('date_format'),
                'weekstart' => $preferences->get('weekstart'),
                'show_week_nb' => $preferences->get('show_week_nb'),
                'show_now_indicator' => $preferences->get('show_now_indicator'),
                'list_days' => $preferences->get('list_days'),
                'default_view' => $preferences->get('default_view'),
                'default_reminder' => $preferences->get('default_reminder'),
            ]
        );

        $response->getBody()->write($body);
        return $response;
    }

    public function saveAction(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $input = (array) ($request->getParsedBody() ?? []);

        $required = [
            'language', 'timezone', 'default_calendar', 'date_format',
            'time_format', 'weekstart', 'show_week_nb',
        ];
        foreach ($required as $key) {
            if (!array_key_exists($key, $input)) {
                throw new HttpBadRequestException(
                    $request,
                    $this->container->get('translator')->trans('messages.error_empty_fields')
                );
            }
        }

        $username = $this->container->get('session')->get('username');
        $preferences = $this->container->get(UserContext::class)->getPreferences();
        $preferences->setAll([
            'language' => $input['language'],
            'timezone' => $input['timezone'],
            'date_format' => $input['date_format'],
            'time_format' => $input['time_format'],
            'weekstart' => $input['weekstart'],
            'default_calendar' => $input['default_calendar'],
            'show_week_nb' => ($input['show_week_nb'] ?? null) === 'true',
            'show_now_indicator' => ($input['show_now_indicator'] ?? null) === 'true',
            'list_days' => $input['list_days'] ?? null,
            'default_view' => $input['default_view'] ?? null,
            'default_reminder' => $this->parseDefaultReminder($input),
        ]);
        $this->container->get('preferences.repository')->save($username, $preferences);

        /** @var RouteParserInterface $routeParser */
        $routeParser = $this->container->get(RouteParserInterface::class);
        return $response
            ->withStatus(302)
            ->withHeader('Location', $routeParser->urlFor('calendar'));
    }

    private function parseDefaultReminder(array $input): ?array
    {
        $count = $input['default_reminder_count'] ?? '';
        $unit = $input['default_reminder_unit'] ?? '';
        $allowed_units = ['minutes', 'hours', 'days', 'weeks', 'months'];

        if ($count === '' || !ctype_digit((string) $count) || !in_array($unit, $allowed_units, true)) {
            return null;
        }

        return ['count' => (int) $count, 'unit' => $unit];
    }
}
