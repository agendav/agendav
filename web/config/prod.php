<?php

return [
    'debug' => false,
    'twig.path' => [__DIR__ . '/../templates'],
    'twig.options' => ['cache' => __DIR__ . '/../var/cache/twig'],

    // Assets
    'stylesheets' => ['agendav.css'],
    'print.stylesheets' => ['agendav.print.css'],
    'scripts' => ['agendav.min.js'],

    // Session parameters
    'session.storage.options' => [
        'name' => 'agendav_sess',
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
    ],

    // Languages
    'languages' => require __DIR__ . '/languages.php',

    // Fullcalendar language packs
    'fullcalendar.languages' => [
        'ca' => 'ca',
        'de_DE' => 'de',
        'es_ES' => 'es',
        'et' => 'et',
        'fi' => 'fi',
        'fr_FR' => 'fr',
        'hr_HR' => 'hr',
        'it_IT' => 'it',
        'ja_JP' => 'ja',
        'nb_NO' => 'nb',
        'nl_NL' => 'nl',
        'pl' => 'pl',
        'pt_BR' => 'pt-br',
        'pt_PT' => 'pt',
        'ru_RU' => 'ru',
        'sk' => 'sk',
        'sv_SE' => 'sv',
        'tr' => 'tr',
    ],
];
