<?php

use AgenDAV\Controller\Authentication;
use AgenDAV\Controller\Calendars;
use AgenDAV\Controller\Event;
use AgenDAV\Controller\JavaScriptCode;
use AgenDAV\Controller\Preferences;
use AgenDAV\Controller\Principals;
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

    // Authenticated routes
    $app->group('', function (RouteCollectorProxy $g) use ($container) {
        $g->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use ($container) {
            // Inline AgenDAVConf/UserPrefs into the page so the JS bootstrap does
            // not depend on a separate /jssettings.js request. Some SSO/reverse-proxy
            // setups (notably YunoHost) gate that subresource as a protected page
            // even when the parent navigation is allowed, breaking <script src>.
            $jsCode = new JavaScriptCode($container);
            $jsconfig = $container->get('twig')->render('jsconfig.html', [
                'site_config' => $jsCode->getSiteConfig($request),
                'preferences' => $jsCode->getPreferences(),
            ]);
            $body = $container->get('twig')->render('calendar.html', [
                'jsconfig' => $jsconfig,
            ]);
            $response->getBody()->write($body);
            return $response;
        })->setName('calendar');

        $g->get('/preferences', Preferences::class . ':indexAction')->setName('preferences');
        $g->post('/preferences', Preferences::class . ':saveAction')->setName('preferences.save');

        $g->get('/calendars', Calendars\Listing::class)->setName('calendars.list');
        $g->post('/calendars', Calendars\Create::class)->setName('calendar.create');
        $g->post('/calendars/delete', Calendars\Delete::class)->setName('calendar.delete');
        $g->post('/calendars/save', Calendars\Save::class)->setName('calendar.save');

        $g->get('/events', Event\Listing::class)->setName('events.list');
        $g->get('/eventbase', Event\GetBase::class)->setName('event.getBase');
        $g->post('/events/drop', Event\Drop::class)->setName('event.drop');
        $g->post('/events/resize', Event\Resize::class)->setName('event.resize');
        $g->post('/events/delete', Event\Delete::class)->setName('event.delete');
        $g->post('/events/save', Event\Save::class)->setName('event.save');

        $g->get('/principals', Principals::class . ':search')->setName('principals.search');

        // Dynamic JavaScript (configuration + per-user prefs)
        $g->get('/jssettings.js', JavaScriptCode::class . ':settingsAction')->setName('settings.js');

        // Session keepalive
        $g->get('/keepalive', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });
    })->add(new AuthMiddleware($container));
};
