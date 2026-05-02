<?php

namespace AgenDAV\Authentication;

use AgenDAV\Controller\Authentication;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP Basic authentication: pulls credentials from PHP_AUTH_USER /
 * PHP_AUTH_PW server parameters and delegates to the Authentication controller
 * for the actual session setup.
 */
class HttpBasic implements AuthenticationMethodInterface
{
    public function __construct(private Authentication $authentication)
    {
    }

    public function login(ServerRequestInterface $request): bool
    {
        $serverParams = $request->getServerParams();
        if (empty($serverParams['HTTP_AUTHORIZATION'])) {
            return false;
        }

        $user = $serverParams['PHP_AUTH_USER'] ?? null;
        $password = $serverParams['PHP_AUTH_PW'] ?? null;

        if ($user === null || $password === null) {
            return false;
        }

        return $this->authentication->processLogin($user, $password);
    }
}
