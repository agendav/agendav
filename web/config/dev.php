<?php

use Silex\Provider\WebProfilerServiceProvider;

// Load defaults
require __DIR__ . '/prod.php';

$app['debug'] = true;
$app['http.debug'] = true;

// Disable twig cache
$twig_options = $app['twig.options'];
unset($twig_options['cache']);
$app['twig.options'] = $twig_options;

$app->register(new WebProfilerServiceProvider(), [
    'profiler.cache_dir' => '/tmp',
]);

// Enable debug logging
$app['monolog.level'] = 'DEBUG';

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

$app['scripts'] = [
    'libs/jquery.js',
    'libs/moment.js',
    'libs/moment-timezone-with-data-2010-2020.min.js',
    'libs/button.js',
    'libs/jquery-ui.js',
    'libs/tab.js',
    'libs/jquery.timepicker.js',
    'libs/jquery.freeow.min.js',
    'libs/jquery.colorPicker.js',
    'libs/imagesloaded.pkg.min.js',
    'libs/jquery.qtip.js',
    'libs/jquery.colorhelpers.js',
    'libs/jquery.serializeobject.js',
    'libs/fullcalendar.js',
    'libs/rrule.js',
    'libs/nlp.js',
    'templates/dust-core.js',
    'templates/dust-helpers.js',
    'templates/templates.js',
    'datetime.js',
    'repeat-form.js',
    'app.js',
];
