<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Symfony\Component\Translation\Loader\XliffFileLoader;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());

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
