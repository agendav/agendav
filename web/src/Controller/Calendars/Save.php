<?php

namespace AgenDAV\Controller\Calendars;

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

use AgenDAV\Uuid;
use AgenDAV\Controller\JSONController;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Transformer\CalendarTransformer;
use League\Fractal\Resource\Collection;
use Silex\Application;

class Save extends JSONController
{
    /**
     * Validates user input
     *
     * @param array $input
     * @return bool
     */
    protected function validateInput(array $input)
    {
        $fields = [
            'calendar',
            'displayname',
            'calendar_color',
        ];

        foreach ($fields as $name) {
            if (empty($input[$name])) {
                return false;
            }
        }

        return true;
    }

    public function execute(array $input, Application $app)
    {
        $url = $input['calendar'];
        $calendar = new Calendar($url, [
            Calendar::DISPLAYNAME => $input['displayname'],
            Calendar::COLOR => $input['calendar_color'],
        ]);

        if ($app['calendar.sharing'] === true) {
            if (isset($input['is_owned']) && $input['is_owned'] === 'true') {
                // TODO Update and save Shares
            } else {
                // TODO update share properties
                return null;
            }
        }

        return $this->updateCalDAV($calendar);
    }

    /**
     * Creates or updates a calendar on the CalDAV server
     *
     * @param AgenDAV\CalDAV\Resource\Calendar $calendar
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateCalDAV($calendar)
    {
        $this->client->updateCalendar($calendar);

        return $this->generateSuccess();
    }
}
