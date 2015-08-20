<?php
/**
 * Site configuration
 *
 * IMPORTANT: These are AgenDAV defaults. Do not change this file, apply your
 * changes to settings.php
 */

// Site title
$app['site.title'] = 'Our calendar';

// Site logo (should be placed in public/img). Optional
$app['site.logo'] = 'agendav_100transp.png';

// Site footer. Optional
$app['site.footer'] = 'AgenDAV ' . \AgenDAV\Version::V;

// Trusted proxy ips
$app['proxies'] = [];

// Database settings
$app['db.options'] = [
        'dbname' => 'agendav',
        'user' => 'root',
        'password' => '',
        'host' => 'localhost',
        'driver' => 'pdo_mysql'
];

// Encryption key
$app['encryption.key'] = 'abcdefg';

// CSRF secret
$app['csrf.secret'] = 'lkjihgfedcba';

// Log path
$app['log.path'] = __DIR__.'/../var/log/';

// Base URL
$app['caldav.baseurl'] = 'http://localhost:81/';

// Authentication method required by CalDAV server (basic or digest)
$app['caldav.authmethod'] = 'basic';

// Whether to show public CalDAV urls
$app['caldav.publicurls'] = true;

// Whether to show public CalDAV urls
$app['caldav.baseurl.public'] = 'https://caldav.server.com';

// Calendar sharing
$app['calendar.sharing'] = false;

// Default timezone
$app['defaults.timezone'] = 'Europe/Madrid';

// Default languajge
$app['defaults.language'] = 'en';

// Default time format. Options: '12' / '24'
$app['defaults.time_format'] = '24';

/*
 * Default date format. Options:
 *
 * - ymd: YYYY-mm-dd
 * - dmy: dd-mm-YYYY
 * - mdy: mm-dd-YYYY
 */
$app['defaults.date_format'] = 'ymd';

// Default first day of week. Options: 0 (Sunday), 1 (Monday)
$app['defaults.weekstart'] = 0;

// Logout redirection. Optional
$app['logout.redirection'] = '';

// Calendar colors
$app['calendar.colors'] = [
    'D4EAEF',
    '3A89C9',
    '107FC9',
    'FAC5C0',
    'FF4E50',
    'BD3737',
    'C9DF8A',
    '77AB59',
    '36802D',
    'F8F087',
    'E6D5C1',
    '3E4147'
];
