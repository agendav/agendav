<?php

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

// Assets
$app['stylesheets'] = [
    'agendav-built-'. \AgenDAV\Version::V .'.min.css',
];

$app['print.stylesheets'] = [
    'agendav-built-print-'. \AgenDAV\Version::V .'.min.css',
];

$app['scripts'] = [
    'agendav-built-'. \AgenDAV\Version::V .'.min.js',
];

// Session parameters
$app['session.storage.options'] = [
    'name' => 'agendav_sess',
    // You should not change cookie_lifetime. Change 'gc_divisor', 'gc_maxlifetime' and other
    // session related settings (http://php.net/session.configuration)
    'cookie_lifetime' => 0,
];

// Configure logging (1/2)
$app['monolog.level'] = 'WARNING';

// Languages
$app['languages'] = require __DIR__ . '/languages.php';

// Fullcalendar language packs
$app['fullcalendar.languages'] = [
    //'br' => 'en', // Missing
    'ca' => 'ca',
    'de_DE' => 'de',
    //'et' => 'en', // Missing
    //'en' => 'en',
    'es_ES' => 'es',
    'fr_FR' => 'fr',
    'hr_HR' => 'hr',
    'it_IT' => 'it',
    'ja_JP' => 'ja',
    'nl_NL' => 'nl',
    'nb_NO' => 'nb',
    'pl' => 'pl',
    'pt_BR' => 'pt-br',
    'fi' => 'fi',
    'sv_SE' => 'sv',
    'ru_RU' => 'ru',
];




// Load configuration settings
if (!file_exists(__DIR__ . '/settings.php')) {
    echo 'settings.php file not found';
    exit(255);
}
require __DIR__ . '/default.settings.php';
require __DIR__ . '/settings.php';

// Configure logging (2/2). Needs log.path
$app['monolog.logfile'] = $app['log.path'] . '/' . date('Y-m-d') . '.log';

$app['locale'] = $app['defaults.language'];
