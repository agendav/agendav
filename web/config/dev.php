<?php

$config = require __DIR__ . '/prod.php';

$config['debug'] = true;
$config['http.debug'] = true;
$config['log.level'] = 'DEBUG';
$config['scripts'] = ['agendav.js'];

// Disable Twig cache in development
unset($config['twig.options']['cache']);
$config['twig.options']['debug'] = true;

return $config;
