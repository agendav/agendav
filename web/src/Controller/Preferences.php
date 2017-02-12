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
        $preferences = $app['user.preferences'];

        $calendars = $app['calendar.finder']->getCalendars();

        $calendars_as_options = [];
        foreach ($calendars as $calendar) {
            $key = $calendar->getUrl();
            $calendars_as_options[$key] = $calendar->getProperty(Calendar::DISPLAYNAME);
        }


        return $app['twig']->render(
            'preferences.html',
            [
                'scripts' => [],
                'available_timezones' => DateHelper::getAllTimeZones(),
                'available_languages' => $app['languages'],
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
            ]
        );
    }

    public function saveAction(Request $request, Application $app)
    {
        $input = $request->request;

        if (!$input->has('language') || !$input->has('timezone') || !$input->has('default_calendar')
        || !$input->has('date_format') || !$input->has('time_format') || !$input->has('weekstart')
        || !$input->has('show_week_nb')) {
            $app->abort('400', $app['translator']->trans('messages.error_empty_fields'));
        }

        $username = $app['session']->get('username');
        $preferences = $app['user.preferences'];
        $preferences->setAll([
            'language' => $input->get('language'),
            'timezone' => $input->get('timezone'),
            'date_format' => $input->get('date_format'),
            'time_format' => $input->get('time_format'),
            'weekstart' => $input->get('weekstart'),
            'default_calendar' => $input->get('default_calendar'),
            'show_week_nb' => $input->get('show_week_nb') == 'true',
            'show_now_indicator' => $input->get('show_now_indicator') == 'true',
            'list_days' => $input->get('list_days'),
            'default_view' => $input->get('default_view'),
        ]);
        $app['preferences.repository']->save($username, $preferences);

        return new RedirectResponse($app['url_generator']->generate('calendar'));
    }
}
