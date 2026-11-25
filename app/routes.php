<?php

use AgenDAV\Controller\Authentication;
use AgenDAV\Controller\Calendars;
use AgenDAV\Controller\Event;
use AgenDAV\Controller\Config;
use AgenDAV\Controller\Preferences;
use AgenDAV\Controller\Principals;
use AgenDAV\Controller\Translations;
use AgenDAV\Middleware\AuthMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $container = $app->getContainer();

    // Public routes (no auth required)
    $app->get('/login', Authentication::class . ':loginAction')->setName('login');
    $app->post('/login', Authentication::class . ':loginAction');
    $app->get('/logout', Authentication::class . ':logoutAction')->setName('logout');
    $app->get('/translations/{language}', Translations::class)->setName('translations');

    // Authenticated routes
    $app->group('', function (RouteCollectorProxy $g) use ($container) {
        $g->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use ($container) {
            $body = $container->get('twig')->render('calendar.html');
            $response->getBody()->write($body);
            return $response;
        })->setName('calendar');

        $g->get('/config', Config::class)->setName('config');

        $g->get('/preferences', Preferences::class . ':indexAction')->setName('preferences');
        $g->post('/preferences/save', Preferences\Save::class)->setName('preferences.save');

        $g->get('/calendars', Calendars\Listing::class)->setName('calendars.list');
        $g->post('/calendars/create', Calendars\Create::class)->setName('calendars.create');
        $g->post('/calendars/delete', Calendars\Delete::class)->setName('calendars.delete');
        $g->post('/calendars/save', Calendars\Save::class)->setName('calendars.save');

        $g->get('/events', Event\Listing::class)->setName('events.list');
        $g->get('/events/definition', Event\Definition::class)->setName('events.definition');
        $g->post('/events/drop', Event\Drop::class)->setName('events.drop');
        $g->post('/events/resize', Event\Resize::class)->setName('events.resize');
        $g->post('/events/delete', Event\Delete::class)->setName('events.delete');
        $g->post('/events/save', Event\Save::class)->setName('events.save');

        $g->get('/principals', Principals::class . ':search')->setName('principals.search');

        // Session keepalive
        $g->get('/keepalive', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });
    })->add(new AuthMiddleware($container));
};
