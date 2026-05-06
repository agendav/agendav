<?php

$config = require __DIR__ . '/prod.php';

$config['debug'] = true;
$config['http.debug'] = true;
$config['log.level'] = 'DEBUG';
$config['scripts'] = ['agendav.js'];

// Disable Twig cache in development
unset($config['twig.options']['cache']);
$config['twig.options']['debug'] = true;

// Local dev usually runs over plain HTTP, so the prod cookie_secure default
// would prevent the session cookie from being sent at all.
$config['session.storage.options']['cookie_secure'] = false;

return $config;
