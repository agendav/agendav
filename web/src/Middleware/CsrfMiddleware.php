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

    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logger = $this->container->get('monolog');
        $logger->debug('Starting CSRF check');

        // Generate a new CSRF token if not present
        $this->container->get('csrf.manager')->getToken($this->container->get('csrf.secret'));

        if (!in_array($request->getMethod(), self::PROTECTED_METHODS, true)) {
            return $handler->handle($request);
        }

        // Token can be supplied either via the X-CSRF-Token header (preferred for
        // JSON / XHR requests) or via the _token field of a form-encoded body.
        $headerToken = $request->getHeaderLine('X-CSRF-Token');
        $body = (array) ($request->getParsedBody() ?? []);
        $bodyToken = isset($body['_token']) ? (string) $body['_token'] : '';
        $submitted = $headerToken !== '' ? $headerToken : $bodyToken;

        if ($submitted === '') {
            $logger->debug('CSRF token missing from request');
            throw new HttpUnauthorizedException($request, 'CSRF token not present');
        }

        $token = new CsrfToken($this->container->get('csrf.secret'), $submitted);

        if (!$this->container->get('csrf.manager')->isTokenValid($token)) {
            $logger->debug('CSRF token is not valid. Aborting');
            throw new HttpUnauthorizedException($request, 'Invalid CSRF token');
        }

        $logger->debug('CSRF token successfully validated');

        return $handler->handle($request);
    }
}
