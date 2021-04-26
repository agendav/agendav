<?php
namespace AgenDAV;

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
use Symfony\Component\Security\Csrf\CsrfToken;

class Csrf
{
    public static function check(Request $request, Application $app)
    {
        $app['monolog']->addDebug('Starting CSRF check');

        // This also generates a new CSRF token if not present
        $current_token = self::getCurrentToken($app);

        if ($request->getMethod() === 'GET') {
            return;
        }

        if (!$request->request->has('_token')) {
            $app['monolog']->addDebug('_token not found on request');
            $app->abort(401, 'CSRF token not present');
            return;
        }

        $csrf_provided_value = $request->request->get('_token');

        $token = new CsrfToken($app['csrf.secret'], $csrf_provided_value);

        $app['monolog']->addDebug('CSRF token sent by user', [
            'value' => $csrf_provided_value,
        ]);

        if (!$app['csrf.manager']->isTokenValid($token)) {
            $app['monolog']->addDebug('CSRF token is not valid. Aborting');
            $app->abort(401, 'Invalid CSRF token');
            return;
        }

        $app['monolog']->addDebug('CSRF token successfully validated');
        return;
    }

    /**
     * Returns current CSRF token
     *
     * @param Silex\Application $app
     * @return Symfony\Component\Security\Csrf\CsrfToken
     */
    public static function getCurrentToken(Application $app)
    {
        return $app['csrf.manager']->getToken($app['csrf.secret']);
    }
}
