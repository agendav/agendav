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
        // HTTPS-only cookie. Override to false in dev.php for local plain-HTTP
        // testing, or set to false here if AgenDAV is intentionally served
        // over HTTP (not recommended).
        'cookie_secure' => true,
        // Lax tolerates top-level navigations from third parties (clicked
        // links) but blocks cross-site form submissions / iframes — the
        // common-sense baseline for a webapp that doesn't need cross-site GETs.
        'cookie_samesite' => 'Lax',
        // Refuse uninitialised session ids supplied by clients (prevents
        // session-fixation pre-login by planting a known cookie).
        'use_strict_mode' => 1,
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
