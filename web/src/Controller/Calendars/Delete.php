<?php

namespace AgenDAV\Controller\Calendars;

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
use League\Fractal\Resource\Collection;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Principal;
use AgenDAV\Data\Transformer\CalendarTransformer;
use Silex\Application;
use Symfony\Component\HttpFoundation\ParameterBag;

class Delete extends JSONController
{
    /**
     * Validates user input
     *
     * @param Symfony\Component\HttpFoundation\ParameterBag $input
     * @return bool
     */
    protected function validateInput(ParameterBag $input)
    {
        $fields = [
            'calendar',
        ];

        foreach ($fields as $name) {
            if (empty($input->get($name))) {
                return false;
            }
        }

        return true;
    }

    public function execute(ParameterBag $input, Application $app)
    {
        $calendar = new Calendar($input->get('calendar'));

        $subscriptions_repository = $app['subscriptions.repository'];
        $user_principal_url = $app['session']->get('principal_url');
        $current_user_principal = new Principal($user_principal_url);

        if ($input->getBoolean('is_subscribed') === true) {
            // If the calendar is a subscription, we remove it from the database
            $subscription = $subscriptions_repository->getSubscriptionByUrl(
                $calendar,
                $current_user_principal
            );

            $subscriptions_repository->remove($subscription);
        } else {
            // Proceed to remove calendar from CalDAV server
            $this->client->deleteCalendar($calendar);
        }

        return $this->generateSuccess($calendar->getUrl());
    }

}
