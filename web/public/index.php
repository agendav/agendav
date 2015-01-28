<?php

ini_set('display_errors', 0);

$vendor_directory = getenv('COMPOSER_VENDOR_DIR');
if ($vendor_directory === false) {
  $vendor_directory = __DIR__.'/../vendor';
}

require_once $vendor_directory . '/autoload.php';

$app = require __DIR__.'/../app/app.php';

require __DIR__.'/../config/prod.php';

// Load DIC definitions
require __DIR__ . '/../app/services.php';

require __DIR__.'/../app/controllers.php';

$app->run();
