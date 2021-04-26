<?php

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

// Available environments: prod, dev
$environment = getenv('AGENDAV_ENVIRONMENT');

if ($environment !== 'prod' && $environment !== 'dev') {
  $environment = 'prod'; // Safe default
}

if ($environment === 'prod') {
  ini_set('display_errors', 0);
}

// Vendor directory for Composer
$vendor_directory = getenv('COMPOSER_VENDOR_DIR');
if ($vendor_directory === false) {
  $vendor_directory = __DIR__.'/../vendor';
}

require_once $vendor_directory . '/autoload.php';

if ($environment === 'dev') {
  Debug::enable();
}

$app = require __DIR__.'/../app/app.php';

$app['environment'] = $environment;

// Load DIC definitions
require __DIR__ . '/../app/services.php';
require __DIR__.'/../app/controllers.php';

require __DIR__.'/../config/' . $environment . '.php';

// Trust configured proxies
Request::setTrustedProxies($app['proxies']);

$app->run();
