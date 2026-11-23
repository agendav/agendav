<?php

namespace AgenDAV\Controller\Preferences;

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

use AgenDAV\Controller\JSONController;
use AgenDAV\UserContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * POST /preferences/save - saves the preferences of the current user.
 * Accepts a form-encoded or JSON body.
 *
 * Optional values that are missing or invalid are stored as null. The
 * configured defaults are applied when the preferences are loaded
 * (Preferences::addDefaults()).
 */
class Save extends JSONController
{
    private const REQUIRED = [
        'language', 'timezone', 'default_calendar', 'date_format',
        'time_format', 'weekstart', 'show_week_nb',
    ];

    protected function validateInput(ParameterBag $input)
    {
        foreach (self::REQUIRED as $name) {
            if (!$input->has($name)) {
                return false;
            }
        }
        return true;
    }

    protected function execute(
        ParameterBag $input,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $username = $this->container->get('session')->get('username');
        $repository = $this->container->get('preferences.repository');
        $preferences = $this->container->get(UserContext::class)->getPreferences();
        $preferences->setAll($this->buildPreferences($input->all()));
        $repository->save($username, $preferences);

        // Reload to return the preferences with defaults, like GET /config
        $saved = $repository->userPreferences($username);
        return $this->generateSuccess($response, ['preferences' => $saved->getAll()]);
    }

    private function buildPreferences(array $input): array
    {
        return [
            'language' => $input['language'],
            'timezone' => $input['timezone'],
            'date_format' => $input['date_format'],
            'time_format' => $input['time_format'],
            'weekstart' => $input['weekstart'],
            'default_calendar' => $input['default_calendar'],
            'show_week_nb' => $this->parseBool($input['show_week_nb']),
            'show_now_indicator' => $this->parseBool($input['show_now_indicator'] ?? false),
            'list_days' => $input['list_days'] ?? null,
            'default_view' => $input['default_view'] ?? null,
            'default_reminder' => $this->parseDefaultReminder($input),
            'hide_weekends' => $this->parseBool($input['hide_weekends'] ?? false),
            'working_hours_start' => $this->parseWorkingHour($input['working_hours_start'] ?? null),
            'working_hours_end' => $this->parseWorkingHour($input['working_hours_end'] ?? null),
            'color_scheme' => $this->parseColorScheme($input['color_scheme'] ?? null),
        ];
    }

    /**
     * Accepts "true"/"false" from forms and true/false from JSON.
     */
    private function parseBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function parseWorkingHour(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^([01]\d|2[0-3]):00$/', $value)) {
            return null;
        }
        return $value;
    }

    private function parseColorScheme(mixed $value): ?string
    {
        if (in_array($value, ['light', 'dark', 'auto'], true)) {
            return $value;
        }
        return null;
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
