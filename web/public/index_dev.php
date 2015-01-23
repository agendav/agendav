<?php

use Symfony\Component\Debug\Debug;

require_once __DIR__.'/../vendor/autoload.php';

Debug::enable();

$app = require __DIR__.'/../app/app.php';
require __DIR__.'/../config/dev.php';

// Load DIC definitions
require __DIR__ . '/../app/services.php';

require __DIR__.'/../app/controllers.php';
$app->run();
