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
use AgenDAV\Data\Principal;
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
            $shares_repository = $app['shares_repository'];
            $user_principal_url = $app['session']->get('principal_url');
            $current_user_principal = new Principal($user_principal_url);

            if (isset($input['is_owned']) && $input['is_owned'] === 'true') {
                // TODO Save shares
            } else {
                $share = $shares_repository->getSourceShare(
                    $calendar,
                    $current_user_principal
                );

                $share->setProperty(Calendar::DISPLAYNAME, $input['displayname']);
                $share->setProperty(Calendar::COLOR, $input['calendar_color']);

                $shares_repository->save($share);

                return $this->generateSuccess();
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
