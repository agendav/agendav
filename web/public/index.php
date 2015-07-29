<?php

use Symfony\Component\Debug\Debug;

// Available environments: prod, dev
$environment = getenv('AGENDAV_ENVIRONMENT');
if ($environment === false || ($environment !== 'prod' && $environment !== 'dev')) {
  $environment = 'prod'; // Safe default
}

// Vendor directory for Composer
$vendor_directory = getenv('COMPOSER_VENDOR_DIR');
if ($vendor_directory === false) {
  $vendor_directory = __DIR__.'/../vendor';
}

if ($environment === 'prod') {
  ini_set('display_errors', 0);
}

require_once $vendor_directory . '/autoload.php';

if ($environment === 'dev') {
  Debug::enable();
}

$app = require __DIR__.'/../app/app.php';

$app['environment'] = $environment;

require __DIR__.'/../config/' . $environment . '.php';

// Load DIC definitions
require __DIR__ . '/../app/services.php';

require __DIR__.'/../app/controllers.php';

$app->run();
