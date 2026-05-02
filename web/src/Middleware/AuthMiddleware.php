<?php

namespace AgenDAV\Middleware;

use AgenDAV\UserContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Ensures the user is authenticated. If session has a username, populates
 * UserContext + the translator with the user's preferences. Otherwise tries
 * configured authentication methods; on failure, redirects to /login (or
 * returns 401 for AJAX requests).
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $this->container->get('session');

        if ($session->has('username')) {
            $this->loadUserContext($session->get('username'));
            return $handler->handle($request);
        }

        // Try alternative authentication methods (HTTP Basic, etc.)
        $methods = $this->container->get('auth.methods');
        if (!empty($methods)) {
            foreach ($methods as $methodClass) {
                $method = $this->container->get($methodClass);
                if ($method->login($request)) {
                    $this->loadUserContext($session->get('username'));
                    return $handler->handle($request);
                }
            }
        }

        // Not authenticated: 401 for XHR, redirect for browsers
        if (strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest') {
            $response = new Response();
            $response->getBody()->write('{}');
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $routeParser = $this->container->get(\Slim\Interfaces\RouteParserInterface::class);
        $loginUrl = $routeParser->urlFor('login');

        $response = new Response();
        return $response->withStatus(302)->withHeader('Location', $loginUrl);
    }

    private function loadUserContext(string $username): void
    {
        $preferences = $this->container->get('preferences.repository')->userPreferences($username);

        $userContext = $this->container->get(UserContext::class);
        $userContext->setPreferences($preferences);
        $userContext->setTimezone((string) $preferences->get('timezone'));

        $this->container->get('translator')->setLocale((string) $preferences->get('language'));
    }
}
