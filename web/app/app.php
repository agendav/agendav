<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\Translation\Loader\XliffFileLoader;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new DoctrineServiceProvider());

// Add some shared data to twig templates
$app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
    $twig->addGlobal('title', $app['site.title']);
    $twig->addGlobal('logo', $app['site.logo']);
    $twig->addGlobal('footer', $app['site.footer']);

    return $twig;
}));


// Translation
$app->register(new TranslationServiceProvider(), [
    'locale_fallbacks' => [ 'en' ]
]);

$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('xliff', new XliffFileLoader());

    $translator->addResource('xliff', __DIR__ . '/../translations/labels.en.xliff', 'en', 'labels');

    return $translator;
}));


return $app;
