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
use AgenDAV\Controller\Calendars\InputHandlers\Shares;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Transformer\CalendarTransformer;
use AgenDAV\Data\Principal;
use AgenDAV\Data\Share;
use AgenDAV\Data\Helper\SharesDiff;
use League\Fractal\Resource\Collection;
use Silex\Application;
use Symfony\Component\HttpFoundation\ParameterBag;

class Save extends JSONController
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
            'displayname',
            'calendar_color',
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
        $url = $input->get('calendar');
        $calendar = new Calendar($url, [
            Calendar::DISPLAYNAME => $input->get('displayname'),
            Calendar::COLOR => $input->get('calendar_color'),
        ]);

        if ($app['calendar.sharing'] === false) {
            // Just send the changes to the server and return
            return $this->updateCalDAV($calendar);
        }

        $shares_repository = $app['shares.repository'];
        $user_principal_url = $app['session']->get('principal_url');
        $current_user_principal = new Principal($user_principal_url);

        if ($input->getBoolean('is_owned') === false) {
            $share = $shares_repository->getSourceShare(
                $calendar,
                $current_user_principal
            );

            $this->applySharedCalendarProperties($share, $input);
            $shares_repository->save($share);

            return $this->generateSuccess();
        }

        // Update shares for calendar owned by current user
        $post_shares = [ 'with' => [], 'rw' => [] ];
        if ($input->has('shares')) {
            $shares = $input->get('shares');
            $post_shares['with'] = $shares['with'];
            $post_shares['rw'] = $shares['rw'];
        }
        $current_shares = $shares_repository->getSharesOnCalendar($calendar);
        $new_shares = Shares::buildFromInput(
            $post_shares['with'],
            $post_shares['rw'],
            $user_principal_url,
            $url
        );

        $shares_diff = new SharesDiff($current_shares);
        $shares_diff->decide($new_shares);
        $acl = $app['acl'];

        foreach($shares_diff->getKeptShares() as $kept_share) {
            $shares_repository->save($kept_share);
            $acl->addGrant(
                $kept_share->getWith(),
                $kept_share->isWritable() ? 'read-write' : 'read-only'
            );
        }

        foreach($shares_diff->getMarkedForRemoval() as $removed_share) {
            $shares_repository->remove($removed_share);
        }

        $this->client->applyACL($calendar, $acl);
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

    /**
     * Saves calendar name and color into the Share object
     *
     * @param AgenDAV\Data\Share $share
     * @param ParameterBag $input
     * @return void
     */
    protected function applySharedCalendarProperties(Share $share, ParameterBag $input)
    {
        $share->setProperty(Calendar::DISPLAYNAME, $input->get('displayname'));
        $share->setProperty(Calendar::COLOR, $input->get('calendar_color'));
    }
}
