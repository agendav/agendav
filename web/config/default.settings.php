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

// Site favicon (should be placed in public/img). Optional
$app['site.favicon'] = 'favicon.ico';

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

// CSRF secret
$app['csrf.secret'] = 'lkjihgfedcba';

// Log path
$app['log.path'] = __DIR__.'/../var/log/';

// Logging level
$app['log.level'] = 'INFO';

// Base URL
$app['caldav.baseurl'] = 'http://localhost:81/';

// Authentication method required by CalDAV server (basic or digest)
$app['caldav.authmethod'] = 'basic';

// Whether to show public CalDAV urls
$app['caldav.publicurls'] = true;

// Whether to show public CalDAV urls
$app['caldav.baseurl.public'] = 'https://caldav.server.com';

// Connection timeout for CalDAV requests (default: wait forever)
$app['caldav.connect.timeout'] = 0;
//
// Response timeout for CalDAV requests (default: wait forever)
$app['caldav.response.timeout'] = 0;

// Whether to verify the SSL certificate (default: true)
$app['caldav.certificate.verify'] = true;

// Email attribute name
$app['principal.email.attribute'] = '{DAV:}email';

// Calendar sharing
$app['calendar.sharing'] = false;

// Calendar sharing permissions. In case of doubt, do not modify them
// These defaults are only useful for DAViCal (http://wiki.davical.org/index.php/Permissions)
$app['calendar.sharing.permissions'] = [
    'owner' => [
        '{DAV:}all',
        '{DAV:}read',
        '{DAV:}unlock',
        '{DAV:}read-acl',
        '{DAV:}read-current-user-privilege-set',
        '{DAV:}write-acl',
        '{urn:ietf:params:xml:ns:caldav}read-free-busy',
        '{DAV:}write',
        '{DAV:}write-properties',
        '{DAV:}write-content',
        '{DAV:}bind',
        '{DAV:}unbind'
     ],
    'read-only' => [ '{DAV:}read', '{urn:ietf:params:xml:ns:caldav}read-free-busy'],
    'read-write' => [ '{DAV:}read', '{DAV:}write', '{urn:ietf:params:xml:ns:caldav}read-free-busy' ],
    'default' => [ '{urn:ietf:params:xml:ns:caldav}read-free-busy' ]
];

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

// Default for showing the week numbers. Options: true/false
$app['defaults.show_week_nb'] = false;

// Default for showing the "now" indicator, a line on current time. Options: true/false
$app['defaults.show_now_indicator'] = true;
//
// Default number of days covered by the "list" (agenda) view. Allowed values: 7, 14 or 31
$app['defaults.list_days'] = 7;

// Default view (month, week, day or list)
$app['defaults.default_view'] = 'month';

// Logout redirection. Optional
$app['logout.redirection'] = '';

// Calendar colors
$app['calendar.colors'] = [
    '03A9F4', // Light blue
    '3F51B5', // Indigo
    'F44336', // Red
    'E91E63', // Pink
    '9C27B0', // Purple
    '673AB7', // Deep purple

    'B3E5FC', // Pale light blue
    'C5CAE9', // Pale Indigo
    'FFCDD2', // Pale red
    'F8BBD0', // Pale pink
    'E1BEE7', // Pale purple
    'D1C4E9', // Pale deep purple

    '4CAF50', // Green
    'FFC107', // Yellow
    'CDDC39', // Lime
    'FF9800', // Orange
    '795548', // Brown
    '9E9E9E', // Gray

    'C8E6C9', // Pale green
    'FFF9C4', // Pale yellow
    'F0F4C3', // Pale lime
    'FFE0B2', // Pale orange
    'D7CCC8', // Pale brown
    'F5F5F5', // Pale gray
];

// Additionnal authentication methods
//$app['auth.methods'] = ['AgenDAV\Authentication\HttpBasic'];
