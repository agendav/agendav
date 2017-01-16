<?php

use Silex\Provider\WebProfilerServiceProvider;

// Load defaults
require __DIR__ . '/prod.php';

$app['debug'] = true;
$app['http.debug'] = true;

// Disable twig cache
$twig_options = $app['twig.options'];
unset($twig_options['cache']);
$app['twig.options'] = $twig_options;

$app->register(new WebProfilerServiceProvider(), [
    'profiler.cache_dir' => '/tmp',
]);

// Enable debug logging
$app['monolog.level'] = 'DEBUG';

$app['scripts'] = [
    'dist/js/agendav.js',
];
