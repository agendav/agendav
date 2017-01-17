<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Silex\Application;

use AgenDAV\DateHelper;

// Trust configured proxies
Request::setTrustedProxies($app['proxies']);

// Authentication
$app->get('/login', '\AgenDAV\Controller\Authentication::loginAction')->bind('login');
$app->post('/login', '\AgenDAV\Controller\Authentication::loginAction');
$app->get('/logout', '\AgenDAV\Controller\Authentication::logoutAction')->bind('logout');



// CSRF protection
$app->before(function(Request $request, Application $app) {
    return \AgenDAV\Csrf::check($request, $app);
});


$controllers = $app['controllers_factory'];
$controllers->get('/', function () use ($app) {
    // Load fullcalendar language pack
    $scripts = $app['scripts'];
    $locale = $app['locale'];

    if (isset($app['fullcalendar.languages'][$locale]) && !empty($app['fullcalendar.languages'][$locale])) {
        $scripts[] = 'fullcalendar-locale/' . $app['fullcalendar.languages'][$locale] . '.js';
    }

    return $app['twig']->render('calendar.html', [ 'scripts' => $scripts ]);
})
->bind('calendar');

$controllers->get('/preferences', '\AgenDAV\Controller\Preferences::indexAction')->bind('preferences');
$controllers->post('/preferences', '\AgenDAV\Controller\Preferences::saveAction')->bind('preferences.save');


$controllers->get('/calendars', '\AgenDAV\Controller\Calendars\Listing::doAction')->bind('calendars.list');
$controllers->post('/calendars', '\AgenDAV\Controller\Calendars\Create::doAction')->bind('calendar.create');
$controllers->post('/calendars/delete', '\AgenDAV\Controller\Calendars\Delete::doAction')->bind('calendar.delete');
$controllers->post('/calendars/save', '\AgenDAV\Controller\Calendars\Save::doAction')->bind('calendar.save');
$controllers->get('/events', '\AgenDAV\Controller\Event\Listing::doAction')->bind('events.list');
$controllers->get('/eventbase', '\AgenDAV\Controller\Event\GetBase::doAction')->bind('event.getBase');
$controllers->post('/events/drop', '\AgenDAV\Controller\Event\Drop::doAction')->bind('event.drop');
$controllers->post('/events/resize', '\AgenDAV\Controller\Event\Resize::doAction')->bind('event.resize');
$controllers->post('/events/delete', '\AgenDAV\Controller\Event\Delete::doAction')->bind('event.delete');
$controllers->post('/events/save', '\AgenDAV\Controller\Event\Save::doAction')->bind('event.save');

$controllers->get('/principals', '\AgenDAV\Controller\Principals::search')->bind('principals.search');

// Dynamic JavaScript code
$controllers->get('/jssettings', '\AgenDAV\Controller\JavaScriptCode::settingsAction')->bind('settings.js');

// Session keepalive
$controllers->get('/keepalive', function() { return ''; });

/**
 * Require being authenticated on every request. If authenticated, just load
 * current user preferences
 */
$controllers->before(function(Request $request, Silex\Application $app) {
    // If user is already authenticated, get his/her preferences and continue
    // processing the request
    if ($app['session']->has('username')) {
        $username = $app['session']->get('username');
        $preferences = $app['preferences.repository']->userPreferences($username);
        $app['user.preferences'] = $preferences;
        $app['user.timezone'] = $preferences->get('timezone');

        // Set application language
        $app['locale'] = $preferences->get('language');
        return;
    }

    if ($request->isXmlHttpRequest()) {
        return new JsonResponse([], 401);
    } else {
        return new RedirectResponse($app['url_generator']->generate('login'));
    }
});

$app->mount('/', $controllers);


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

    return new Response(
        $app['twig']->resolveTemplate($templates)->render([
            'code' => $code,
            'message' => $e->getMessage(),
        ]),
        $code
    );
});
