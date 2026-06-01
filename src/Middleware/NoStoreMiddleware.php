<?php

namespace AgenDAV\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stamps Cache-Control: no-store on every response. Login pages, calendar
 * UI, preferences and JSON endpoints all carry per-user state (session
 * cookies, CSRF tokens, calendar data); none of it should sit in shared
 * proxies or browser BFCache. Static assets are served by Apache directly
 * via .htaccess and never reach this middleware.
 */
class NoStoreMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response
            ->withHeader('Cache-Control', 'no-store, max-age=0')
            ->withHeader('Pragma', 'no-cache');
    }
}
