<?php

namespace AgenDAV\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sets the global variables AgenDAV templates expect (title, logo, csrf_token,
 * lang, scripts, ...). Runs per-request because csrf_token and lang depend on
 * session state.
 */
class TwigGlobalsMiddleware implements MiddlewareInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $twig = $this->container->get('twig');

        $twig->addGlobal('environment', $this->container->get('environment'));
        $twig->addGlobal('title', $this->container->get('site.title'));
        $twig->addGlobal('logo', $this->container->get('site.logo'));
        $twig->addGlobal('favicon', $this->container->get('site.favicon'));
        $twig->addGlobal('footer', $this->container->get('site.footer'));
        $twig->addGlobal('stylesheets', $this->container->get('stylesheets'));
        $twig->addGlobal('print_stylesheets', $this->container->get('print.stylesheets'));
        $twig->addGlobal('scripts', $this->container->get('scripts'));
        $translator = $this->container->get('translator');
        $locale = $translator->getLocale();
        $twig->addGlobal('lang', $locale);
        $twig->addGlobal(
            'translations',
            $translator->getCatalogue($locale)->all('messages')
        );

        $twig->addGlobal(
            'fullcalendar_languages',
            $this->container->has('fullcalendar.languages')
                ? $this->container->get('fullcalendar.languages')
                : []
        );

        $session = $this->container->get('session');
        $twig->addGlobal('displayname', $session->has('displayname') ? $session->get('displayname') : '');
        $twig->addGlobal('calendar_subscriptions', $this->container->get('calendar.subscriptions'));

        $twig->addGlobal(
            'csrf_token',
            $this->container->get('csrf.manager')->getToken($this->container->get('csrf.secret'))
        );

        return $handler->handle($request);
    }
}
