<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\AssetServiceProvider;
use Symfony\Component\Translation\Loader\PhpFileLoader;

$app = new Application();
$app->register(new RoutingServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new DoctrineServiceProvider());
$app->register(new MonologServiceProvider(), [
    'monolog.name' => 'agendav',
]);

$app->register(new AssetServiceProvider(), [
    'assets.version' => 'v' . \AgenDAV\Version::V,
    'assets.named_packages' => [
        'css' => [ 'base_path' => '/dist/css', 'version' => \AgenDAV\Version::V ],
        'js' => [ 'base_path' => '/dist/js', 'version' => \AgenDAV\Version::V ],
        'img' => [ 'base_path' => '/img', 'version' => \AgenDAV\Version::V ],
    ],
]);

// Add some shared data to twig templates
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    $twig->addGlobal('environment', $app['environment']);
    $twig->addGlobal('title', $app['site.title']);
    $twig->addGlobal('logo', $app['site.logo']);
    $twig->addGlobal('favicon', $app['site.favicon']);
    $twig->addGlobal('footer', $app['site.footer']);
    $twig->addGlobal('lang', $app['translator']->getLocale());

    // Assets
    $twig->addGlobal('stylesheets', $app['stylesheets']);
    $twig->addGlobal('print_stylesheets', $app['print.stylesheets']);
    $twig->addGlobal('scripts', $app['scripts']);

    // CSRF token
    $twig->addGlobal('csrf_token', \AgenDAV\Csrf::getCurrentToken($app));

    return $twig;
});


// Translation
$app->register(new TranslationServiceProvider(), [
    'locale_fallbacks' => [ 'en' ]
]);

$app['translator'] = $app->extend('translator', function($translator, $app) {
    $translator->addLoader('php', new PhpFileLoader());

    $languages = array_keys($app['languages']);

    foreach ($languages as $language) {
        $translator->addResource('php', __DIR__ . '/../lang/'.$language.'.php', $language);
    }

    return $translator;
});

// Default environment: production
$app['environment'] = 'prod';

return $app;
