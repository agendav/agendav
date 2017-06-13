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

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Authentication controller for login/logout actions
 */
class Authentication
{
    public function loginAction(Request $request, Application $app)
    {
        $success = false;
        $template_vars = [];

        if ($request->isMethod('POST')) {
            $user = $request->request->get('user');
            $password = $request->request->get('password');

            if (empty($user) || empty($password)) {
                $template_vars['error'] = $app['translator']->trans('messages.error_empty_fields');
            } else {
                $success = $this->processLogin($user, $password, $app);

                if ($success === true) {
                    $app['monolog']->addInfo(
                        sprintf('User %s logged in from %s', $user, $request->getClientIp())
                    );
                    return new RedirectResponse(
                        $app['url_generator']->generate('calendar')
                    );
                }

                $app['monolog']->addInfo(
                    sprintf('Failed login for %s from %s', $user, $request->getClientIp())
                );
                $template_vars['error'] =  $app['translator']->trans('messages.error_auth');
            }
        }

        return $app['twig']->render('login.html', $template_vars);
    }

    public function logoutAction(Request $request, Application $app)
    {
        $app['session']->clear();

        $url = $app['url_generator']->generate('login');
        if (!empty($app['logout.redirection'])) {
            $url = $app['logout.redirection'];
        }

        return new RedirectResponse($url);
    }

    /**
     * Uses passed credentials to authenticate a user. In case they are valid, session is
     * populated with user data (principal, etc)
     *
     * @param string $user
     * @param string $password
     * @param Application $app
     * @return bool false if authentication failed, true otherwise
     */
    public function processLogin($user, $password, Application $app)
    {
        $app['http.client']->setAuthentication($user, $password, $app['caldav.authmethod']);

        $caldav_client = $app['caldav.client'];

        if (!$caldav_client->canAuthenticate()) {
            return false;
        }

        $app['session']->set('username', $user);
        $app['session']->set('password', $password);
        $principal_url = $caldav_client->getCurrentUserPrincipal();

        $principals_repository = $app['principals.repository'];
        $principal = $principals_repository->get($principal_url);

        $app['session']->set('principal_url', $principal_url);
        $app['session']->set('calendar_home_set', $caldav_client->getCalendarHomeSet($principal));
        $app['session']->set('displayname', $principal->getDisplayName());

        return true;
    }
}
