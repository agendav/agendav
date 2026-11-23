<?php

namespace AgenDAV\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stamps a baseline of security headers on every response. Static assets
 * are served by Apache directly via .htaccess and never reach this layer.
 *
 * - X-Frame-Options + frame-ancestors lock out click-jacking iframes.
 * - X-Content-Type-Options stops MIME sniffing of /config and /calendars.
 * - Referrer-Policy keeps full URLs off cross-origin Referer headers.
 * - CSP allows no inline scripts, the frontend loads its settings from
 *   /config. It allows 'unsafe-inline' for styles because login.html uses
 *   inline style attributes.
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    private const CSP = "default-src 'self'; "
        . "script-src 'self'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data:; "
        . "font-src 'self' data:; "
        . "connect-src 'self'; "
        . "frame-ancestors 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'";

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'same-origin')
            ->withHeader('Content-Security-Policy', self::CSP);
    }
}
