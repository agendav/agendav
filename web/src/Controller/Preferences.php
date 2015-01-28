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

use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\DateHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Silex\Application;

/**
 * This class is used to find all accessible calendars for an user
 */
class Preferences
{
    public function indexAction(Request $request, Application $app)
    {
        $preferences = $app['preferences.repository']->userPreferences(
            $app['session']->get('username')
        );

        $calendars = $app['calendar.finder']->getCalendars();

        $calendars_as_options = [];
        foreach ($calendars as $calendar) {
            $key = $calendar->getUrl();
            $calendars_as_options[$key] = $calendar->getProperty(Calendar::DISPLAYNAME);
        }


        return $app['twig']->render(
            'preferences.html',
            [
                'available_timezones' => DateHelper::getAllTimeZones(),
                'available_languages' => $app['languages'],
                'timezone' => 'Europe/Madrid',
                'calendars' => $calendars_as_options,
                'default_calendar' => $preferences->get('default_calendar'),
                'language' => $preferences->get('language', $app['defaults.language']),
            ]
        );
    }

    public function saveAction(Request $request, Application $app)
    {
        $input = $request->request;

        if (!$input->has('language') || !$input->has('timezone') || !$input->has('default_calendar')) {
            $app->abort('400', $app['translator']->trans('messages.error_empty_fields'));
        }

        $username = $app['session']->get('username');
        $preferences = $app['preferences.repository']->userPreferences($username);
        $preferences->setAll([
            'language' => $input->get('language'),
            'timezone' => $input->get('timezone'),
            'default_calendar' => $input->get('default_calendar'),
        ]);
        $app['preferences.repository']->save($username, $preferences);

        return new RedirectResponse($app['url_generator']->generate('calendar'));
    }
}
