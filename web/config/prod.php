<?php

$app['twig.path'] = array(__DIR__.'/../templates');

// Assets
$app['stylesheets'] = [
    'agendav.css',
    'jquery-ui.css',
    'jquery-ui.structure.css',
    'jquery-ui.theme.css',
    'fullcalendar.css',
    'jquery.qtip.css',
    'freeow.css',
    'jquery.timepicker.css',
    'colorpicker.css',
];

$app['print.stylesheets'] = [
    'app.print.css',
    'fullcalendar.print.css',
];

// Session parameters
$app['session.storage.options'] = [
    'name' => 'agendav_sess',
    'cookie_lifetime' => 0,
    'refresh' => 300,
];



// Load configuration settings
require __DIR__ . '/settings.php';
