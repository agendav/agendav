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
use AgenDAV\Data\Preferences;
use AgenDAV\UserContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * POST /preferences/save - saves the preferences of the current user.
 * Accepts a form-encoded or JSON body.
 *
 * Every value is checked against its allowed values; invalid input is
 * rejected with 400. Optional values that are missing are stored as null.
 * The configured defaults are applied when the preferences are loaded
 * (Preferences::addDefaults()).
 */
class Save extends JSONController
{
    protected function validateInput(ParameterBag $input)
    {
        return $this->buildPreferences($input->all()) !== null;
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

    /**
     * @return array|null Normalized preferences, null if a value is missing or invalid
     */
    private function buildPreferences(array $input): ?array
    {
        $languages = array_keys($this->container->get('languages'));

        $language = $input['language'] ?? null;
        $timezone = $input['timezone'] ?? null;
        $defaultCalendar = $input['default_calendar'] ?? null;
        $dateFormat = $input['date_format'] ?? null;
        $timeFormat = (string) ($input['time_format'] ?? '');
        $weekstart = $this->parseInt($input['weekstart'] ?? null);
        $showWeekNb = $this->parseBool($input['show_week_nb'] ?? null);
        $showNowIndicator = $this->parseBool($input['show_now_indicator'] ?? false);
        $hideWeekends = $this->parseBool($input['hide_weekends'] ?? false);
        $listDays = $this->parseInt($input['list_days'] ?? null);
        $defaultView = $input['default_view'] ?? null;
        $colorScheme = $input['color_scheme'] ?? null;
        $workingHoursStart = $input['working_hours_start'] ?? null;
        $workingHoursEnd = $input['working_hours_end'] ?? null;
        $reminderCount = $input['default_reminder_count'] ?? null;
        $reminderUnit = $input['default_reminder_unit'] ?? null;

        if (!in_array($language, $languages, true)
            || !in_array($timezone, \DateTimeZone::listIdentifiers(), true)
            || !is_string($defaultCalendar)
            || !in_array($dateFormat, Preferences::DATE_FORMATS, true)
            || !in_array($timeFormat, Preferences::TIME_FORMATS, true)
            || !in_array($weekstart, Preferences::WEEKSTARTS, true)
            || $showWeekNb === null
            || $showNowIndicator === null
            || $hideWeekends === null
            || (isset($input['list_days']) && !in_array($listDays, Preferences::LIST_DAYS, true))
            || ($defaultView !== null && !in_array($defaultView, Preferences::VIEWS, true))
            || ($colorScheme !== null && !in_array($colorScheme, Preferences::COLOR_SCHEMES, true))
            || !$this->isOptionalHour($workingHoursStart, '/^([01]\d|2[0-3]):00$/')
            || !$this->isOptionalHour($workingHoursEnd, '/^(0[1-9]|1\d|2[0-4]):00$/')
            || !$this->isOptionalReminder($reminderCount, $reminderUnit)
        ) {
            return null;
        }

        // An empty form field means "not set"
        $defaultReminder = null;
        if ($reminderCount !== null && $reminderCount !== '') {
            $defaultReminder = ['count' => $this->parseInt($reminderCount), 'unit' => $reminderUnit];
        }

        return [
            'language' => $language,
            'timezone' => $timezone,
            'date_format' => $dateFormat,
            'time_format' => $timeFormat,
            'weekstart' => $weekstart,
            'default_calendar' => $defaultCalendar,
            'show_week_nb' => $showWeekNb,
            'show_now_indicator' => $showNowIndicator,
            'list_days' => $listDays,
            'default_view' => $defaultView,
            'default_reminder' => $defaultReminder,
            'hide_weekends' => $hideWeekends,
            'working_hours_start' => $workingHoursStart ?: null,
            'working_hours_end' => $workingHoursEnd ?: null,
            'color_scheme' => $colorScheme,
        ];
    }

    /**
     * Accepts true/false from JSON and "true"/"false", "1"/"0" from forms.
     *
     * @return bool|null null if the value is missing or not a boolean
     */
    private function parseBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Accepts integers from JSON and digit strings from forms.
     */
    private function parseInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }
        return null;
    }

    /**
     * True if the value is missing, empty or a full hour like "08:00" matching $pattern
     */
    private function isOptionalHour(mixed $value, string $pattern): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return is_string($value) && preg_match($pattern, $value) === 1;
    }

    /**
     * True if the count is missing or empty (no reminder), or a number with a known unit
     */
    private function isOptionalReminder(mixed $count, mixed $unit): bool
    {
        if ($count === null || $count === '') {
            return true;
        }
        return $this->parseInt($count) !== null && in_array($unit, Preferences::REMINDER_UNITS, true);
    }
}
