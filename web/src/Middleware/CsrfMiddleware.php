<?php

namespace AgenDAV\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Validates a CSRF token on state-changing requests. Tokens are stored in the
 * session via Symfony's SessionTokenStorage.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logger = $this->container->get('monolog');
        $logger->debug('Starting CSRF check');

        // Generate a new CSRF token if not present
        $this->container->get('csrf.manager')->getToken($this->container->get('csrf.secret'));

        if ($request->getMethod() === 'GET') {
            return $handler->handle($request);
        }

        $body = (array) ($request->getParsedBody() ?? []);
        if (!array_key_exists('_token', $body)) {
            $logger->debug('_token not found on request');
            throw new HttpUnauthorizedException($request, 'CSRF token not present');
        }

        $token = new CsrfToken($this->container->get('csrf.secret'), (string) $body['_token']);

        $logger->debug('CSRF token sent by user', ['value' => $body['_token']]);

        if (!$this->container->get('csrf.manager')->isTokenValid($token)) {
            $logger->debug('CSRF token is not valid. Aborting');
            throw new HttpUnauthorizedException($request, 'Invalid CSRF token');
        }

        $logger->debug('CSRF token successfully validated');

        return $handler->handle($request);
    }
}
