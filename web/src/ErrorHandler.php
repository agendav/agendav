<?php

namespace AgenDAV;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Psr7\Response;

/**
 * Picks errors/<code>.html (or errors/<digit>x.html / errors/<digit>xx.html)
 * to render unhandled exceptions, falling back to errors/default.html. In
 * debug mode renders the exception details inline so stack traces show up.
 */
class ErrorHandler
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function __invoke(
        ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $code = $exception instanceof HttpException ? $exception->getCode() : 500;

        if ($displayErrorDetails) {
            $body = sprintf(
                '<h1>%d %s</h1><pre>%s</pre>',
                $code,
                htmlspecialchars(get_class($exception) . ': ' . $exception->getMessage()),
                htmlspecialchars((string) $exception)
            );
            $response = new Response();
            $response->getBody()->write($body);
            return $response->withStatus($code)->withHeader('Content-Type', 'text/html; charset=utf-8');
        }

        // TODO(phase4-followup): in prod mode an unknown route returns HTTP 500
        // with empty body instead of rendering errors/404.html. The dev path
        // works, so the failure is in this prod branch — likely Twig template
        // resolution or render. Reproduce with AGENDAV_ENVIRONMENT=prod and
        // tail apache's error log (display_errors=0 swallows it from the body).
        $templates = [
            sprintf('errors/%d.html', $code),
            sprintf('errors/%dx.html', intdiv($code, 10)),
            sprintf('errors/%dxx.html', intdiv($code, 100)),
            'errors/default.html',
        ];

        $body = $this->container->get('twig')->resolveTemplate($templates)->render([
            'code' => $code,
            'message' => $exception->getMessage(),
        ]);

        $response = new Response();
        $response->getBody()->write($body);
        return $response->withStatus($code);
    }
}
