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
        $template_vars = [];

        if ($request->isMethod('POST')) {
            $result = $this->processLogin($request, $app);

            if ($result === true) {
                return new RedirectResponse(
                    $app['url_generator']->generate('calendar')
                );
            }

            $template_vars['error'] = $result;
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

    protected function processLogin(Request $request, Application $app)
    {
        $user = $request->request->get('user');
        $password = $request->request->get('password');

        if (empty($user) || empty($password)) {
            return $app['translator']->trans('empty.fields');
        }

        $app['http.client']->setAuthentication($user, $password, $app['caldav.authmethod']);

        $caldav_client = $app['caldav.client'];

        if (!$caldav_client->canAuthenticate()) {
            return $app['translator']->trans('auth.error');
        }

        $app['session']->set('username', $user);
        $app['session']->set('password', $password);
        $principal_url = $caldav_client->getCurrentUserPrincipal();
        $app['session']->set('principal_url', $principal_url);
        $app['session']->set('calendar_home_set', $caldav_client->getCalendarHomeSet($principal_url));

        return true;
    }
}
