<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AgenDAV\DateHelper;

// Trust configured proxies
Request::setTrustedProxies($app['proxies']);


$app->get('/', function () use ($app) {
    return $app['twig']->render(
        'calendar.html',
        [
        ]
    );
})
->bind('calendar');

$app->get('/preferences', function () use ($app) {
    return $app['twig']->render(
        'preferences.html',
        [
            'available_timezones' => DateHelper::getAllTimeZones(),
            'timezone' => 'Europe/Madrid',
            'calendars' => [],
        ]
    );
})
->bind('preferences');

// Authentication
$app->get('/login', '\AgenDAV\Controller\Authentication::loginAction')->bind('login');
$app->post('/login', '\AgenDAV\Controller\Authentication::loginAction');

$app->get('/logout', '\AgenDAV\Controller\Authentication::logoutAction')->bind('logout');

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html',
        'errors/'.substr($code, 0, 2).'x.html',
        'errors/'.substr($code, 0, 1).'xx.html',
        'errors/default.html',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
