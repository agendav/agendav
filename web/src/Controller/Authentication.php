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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteParserInterface;

class Authentication
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function loginAction(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $template_vars = [];

        // GET: try alternative auth methods (HTTP Basic, ...) before showing
        // the form. Lets clients that follow a 302 redirect chain authenticate
        // transparently with credentials already on the request.
        if ($request->getMethod() === 'GET' && !$this->container->get('session')->has('username')) {
            foreach ((array) $this->container->get('auth.methods') as $methodClass) {
                if ($this->container->get($methodClass)->login($request)) {
                    /** @var RouteParserInterface $routeParser */
                    $routeParser = $this->container->get(RouteParserInterface::class);
                    return $response
                        ->withStatus(302)
                        ->withHeader('Location', $routeParser->urlFor('calendar'));
                }
            }
        }

        if ($request->getMethod() === 'POST') {
            $body = (array) ($request->getParsedBody() ?? []);
            $user = $body['user'] ?? null;
            $password = $body['password'] ?? null;
            $translator = $this->container->get('translator');
            $logger = $this->container->get('monolog');
            $serverParams = $request->getServerParams();
            $clientIp = $serverParams['REMOTE_ADDR'] ?? '?';

            if (empty($user) || empty($password)) {
                $template_vars['error'] = $translator->trans('messages.error_empty_fields');
            } else {
                $success = $this->processLogin($user, $password);

                // Username is user-submitted; route it through Monolog's
                // context (which serialises values via JSON) instead of the
                // message body, so CR/LF in $user can't forge log lines.
                // Truncate as defence-in-depth against unbounded inputs.
                $logContext = ['user' => substr((string) $user, 0, 64), 'ip' => $clientIp];

                if ($success === true) {
                    $logger->info('User logged in', $logContext);
                    /** @var RouteParserInterface $routeParser */
                    $routeParser = $this->container->get(RouteParserInterface::class);
                    return $response
                        ->withStatus(302)
                        ->withHeader('Location', $routeParser->urlFor('calendar'));
                }

                $logger->info('Failed login', $logContext);
                $template_vars['error'] = $translator->trans('messages.error_auth');
            }
        }

        $body = $this->container->get('twig')->render('login.html', $template_vars);
        $response->getBody()->write($body);
        return $response;
    }

    public function logoutAction(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $this->container->get('session')->invalidate();

        $url = $this->container->get('logout.redirection');
        if (empty($url)) {
            /** @var RouteParserInterface $routeParser */
            $routeParser = $this->container->get(RouteParserInterface::class);
            $url = $routeParser->urlFor('login');
        }

        return $response->withStatus(302)->withHeader('Location', $url);
    }

    /**
     * Authenticates a user using passed credentials. Populates the session on
     * success.
     *
     * @return bool true on success
     */
    public function processLogin(string $user, string $password): bool
    {
        $this->container->get('http.client')->setAuthentication(
            $user,
            $password,
            $this->container->get('caldav.authmethod')
        );

        $caldav_client = $this->container->get('caldav.client');

        if (!$caldav_client->canAuthenticate()) {
            return false;
        }

        // Resolve the principal BEFORE touching the session. A null
        // principal_url (network race, malformed CalDAV response) or a
        // throw from the principals repository must leave the session
        // untouched — otherwise AuthMiddleware would let a partially
        // authenticated user through with a null principal, widening
        // ACL / share visibility.
        $principal_url = $caldav_client->getCurrentUserPrincipal();
        if (empty($principal_url)) {
            return false;
        }
        $principal = $this->container->get('principals.repository')->get($principal_url);
        $calendar_home_set = $caldav_client->getCalendarHomeSet($principal);

        $session = $this->container->get('session');
        // Defeat session fixation: regenerate the session id and destroy the
        // old store before writing any authenticated state. Refresh the CSRF
        // token afterwards so it is bound to the new session id.
        $session->migrate(true);
        $this->container->get('csrf.manager')->refreshToken($this->container->get('csrf.secret'));

        $session->set('username', $user);
        $session->set('password.encrypted', $this->container->get('password.cipher')->encrypt($password));
        $session->set('principal_url', $principal_url);
        $session->set('calendar_home_set', $calendar_home_set);
        $session->set('displayname', $principal->getDisplayName());

        return true;
    }
}
