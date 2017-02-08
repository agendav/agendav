<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Symfony\Component\Translation\Loader\PhpFileLoader;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new DoctrineServiceProvider());
$app->register(new MonologServiceProvider(), [
    'monolog.name' => 'agendav',
]);

// Add some shared data to twig templates
$app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
    $twig->addGlobal('environment', $app['environment']);
    $twig->addGlobal('title', $app['site.title']);
    $twig->addGlobal('logo', $app['site.logo']);
    $twig->addGlobal('footer', $app['site.footer']);

    // Assets
    $assets_root = '';
    if ($app['environment'] === 'prod') {
        $assets_root = '';
    }
    $twig->addGlobal('assets_root', $assets_root);

    $twig->addGlobal('stylesheets', $app['stylesheets']);
    $twig->addGlobal('print_stylesheets', $app['print.stylesheets']);
    $twig->addGlobal('scripts', $app['scripts']);

    // CSRF token
    $twig->addGlobal('csrf_token', \AgenDAV\Csrf::getCurrentToken($app));

    return $twig;
}));


// Translation
$app->register(new TranslationServiceProvider(), [
    'locale_fallbacks' => [ 'en' ]
]);

$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('php', new PhpFileLoader());

    $languages = array_keys($app['languages']);

    foreach ($languages as $language) {
        $translator->addResource('php', __DIR__ . '/../lang/'.$language.'.php', $language);
    }

    return $translator;
}));

// Default environment: production
$app['environment'] = 'prod';


return $app;
