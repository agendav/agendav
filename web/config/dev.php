<?php

// Load defaults
require __DIR__ . '/prod.php';

$app['debug'] = true;
$app['http.debug'] = true;

// Disable twig cache
$twig_options = $app['twig.options'];
unset($twig_options['cache']);
$app['twig.options'] = $twig_options;

// Enable debug logging
$app['monolog.level'] = 'DEBUG';

$app['scripts'] = [
    'agendav.js',
];
