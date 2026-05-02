<?php

namespace AgenDAV\Authentication;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface for alternative authentication methods (HTTP Basic, etc.).
 */
interface AuthenticationMethodInterface
{
    /**
     * Attempt to authenticate the user from the given request. Returns true
     * if the session has been populated with a logged-in user.
     */
    public function login(ServerRequestInterface $request): bool;
}
