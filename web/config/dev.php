<?php

use Silex\Provider\WebProfilerServiceProvider;

// Set ENABLE_AGENDAV_DEVELOPMENT environment variable to enable this front
// controller
if (getenv('ENABLE_AGENDAV_DEVELOPMENT') === false) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. ENABLE_AGENDAV_DEVELOPMENT not set');
}


// Load defaults
require __DIR__ . '/prod.php';

$app['debug'] = true;
$app['http.debug'] = true;

$app->register(new WebProfilerServiceProvider(), [
    'profiler.cache_dir' => '/tmp'
]);

// Enable debug logging
$app['monolog.level'] = 'DEBUG';
