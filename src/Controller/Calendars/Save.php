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
use AgenDAV\Controller\Calendars\InputHandlers\Shares;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Subscription;
use AgenDAV\Data\Principal;
use AgenDAV\Data\Share;
use AgenDAV\Data\Helper\SharesDiff;
use AgenDAV\Repositories\SubscriptionsRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class Save extends JSONController
{
    protected function validateInput(ParameterBag $input)
    {
        foreach (['calendar', 'displayname', 'calendar_color'] as $name) {
            if (empty($input->get($name))) {
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
        $url = $input->get('calendar');
        $calendar = new Calendar($url, [
            Calendar::DISPLAYNAME => $input->get('displayname'),
            Calendar::COLOR => $input->get('calendar_color'),
        ]);

        if ($this->container->get('calendar.sharing') === false) {
            return $this->updateCalDAV($calendar, $response);
        }

        $shares_repository = $this->container->get('shares.repository');
        $subscriptions_repository = $this->container->get('subscriptions.repository');
        $user_principal_url = $this->container->get('session')->get('principal_url');
        $current_user_principal = new Principal($user_principal_url);

        if ($input->getBoolean('is_subscribed') === true) {
            // Subscribed calendar properties are saved locally
            $calendar->setSubscribed(true);
            $subscription = $subscriptions_repository->getSubscriptionByUrl(
                $calendar,
                $current_user_principal
            );

            $this->applySubscribedCalendarProperties($subscription, $input);
            $subscriptions_repository->save($subscription);
            return $this->generateSuccess($response);
        }

        if ($input->getBoolean('is_owned') === false) {
            $share = $shares_repository->getSourceShare($calendar, $current_user_principal);
            $this->applySharedCalendarProperties($share, $input);
            $shares_repository->save($share);
            return $this->generateSuccess($response);
        }

        // Update shares for calendar owned by current user
        $post_shares = ['with' => [], 'rw' => []];
        if ($input->has('shares')) {
            $shares = $input->get('shares');
            $post_shares['with'] = $shares['with'] ?? [];
            $post_shares['rw'] = $shares['rw'] ?? [];
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
        $acl = $this->container->get('acl');

        foreach ($shares_diff->getKeptShares() as $kept_share) {
            $shares_repository->save($kept_share);
            $acl->addGrant(
                $kept_share->getWith(),
                $kept_share->isWritable() ? 'read-write' : 'read-only'
            );
        }

        foreach ($shares_diff->getMarkedForRemoval() as $removed_share) {
            $shares_repository->remove($removed_share);
        }

        $this->client->applyACL($calendar, $acl);
        return $this->updateCalDAV($calendar, $response);
    }

    protected function updateCalDAV(Calendar $calendar, ResponseInterface $response): ResponseInterface
    {
        $this->client->updateCalendar($calendar);
        return $this->generateSuccess($response);
    }

    protected function applySharedCalendarProperties(Share $share, ParameterBag $input): void
    {
        $share->setProperty(Calendar::DISPLAYNAME, $input->get('displayname'));
        $share->setProperty(Calendar::COLOR, $input->get('calendar_color'));
    }

    /**
     * Saves calendar name and color into the Subscription object
     *
     * @param AgenDAV\Data\Subscription $subscription
     * @param ParameterBag $input
     * @return void
     */
    protected function applySubscribedCalendarProperties(Subscription $subscription, ParameterBag $input)
    {
        $subscription->setProperty(Calendar::DISPLAYNAME, $input->get('displayname'));
        $subscription->setProperty(Calendar::COLOR, $input->get('calendar_color'));
    }
}
