<?php

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

// Assets
$app['stylesheets'] = [
    'agendav.css',
];

$app['print.stylesheets'] = [
    'agendav.print.css',
];

$app['scripts'] = [
    'agendav.min.js',
];

// Session parameters
$app['session.storage.options'] = [
    'name' => 'agendav_sess',
    // You should not change cookie_lifetime. Change 'gc_divisor', 'gc_maxlifetime' and other
    // session related settings (http://php.net/session.configuration)
    'cookie_lifetime' => 0,
    'cookie_httponly' => true,
];

// Languages
$app['languages'] = require __DIR__ . '/languages.php';

// Fullcalendar language packs
$app['fullcalendar.languages'] = [
    //'br'  => 'en', // Missing
    'ca'    => 'ca',
    'de_DE' => 'de',
    //'en'  => 'en',
    'es_ES' => 'es',
    'et'    => 'et',
    'fi'    => 'fi',
    'fr_FR' => 'fr',
    'hr_HR' => 'hr',
    'it_IT' => 'it',
    'ja_JP' => 'ja',
    'nb_NO' => 'nb',
    'nl_NL' => 'nl',
    'pl'    => 'pl',
    'pt_BR' => 'pt-br',
    'pt_PT' => 'pt',
    'ru_RU' => 'ru',
    'sk'    => 'sk',
    'sv_SE' => 'sv',
    'tr'    => 'tr',
];

// Load configuration settings
if (!file_exists(__DIR__ . '/settings.php')) {
    echo 'settings.php file not found';
    exit(255);
}

require __DIR__ . '/default.settings.php';
require __DIR__ . '/settings.php';
