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
        if ($this->container->get('calendar.read_only') === true) {
            return $this->redirectToCalendar($response);
        }

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
                'scripts' => ['preferences.js'],
                'available_views' => \AgenDAV\Data\Preferences::VIEWS,
                'available_list_days' => \AgenDAV\Data\Preferences::LIST_DAYS,
                'available_reminder_units' => \AgenDAV\Data\Preferences::REMINDER_UNITS,
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
                'hide_weekends' => $preferences->get('hide_weekends'),
                'working_hours_start' => $preferences->get('working_hours_start'),
                'working_hours_end' => $preferences->get('working_hours_end'),
                'color_scheme' => $preferences->get('color_scheme'),
            ]
        );

        $response->getBody()->write($body);
        return $response;
    }

    private function redirectToCalendar(ResponseInterface $response): ResponseInterface
    {
        /** @var RouteParserInterface $routeParser */
        $routeParser = $this->container->get(RouteParserInterface::class);
        return $response
            ->withStatus(302)
            ->withHeader('Location', $routeParser->urlFor('calendar'));
    }
}
