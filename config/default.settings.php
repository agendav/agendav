<?php
/**
 * Site configuration defaults.
 *
 * IMPORTANT: These are AgenDAV defaults. Do not change this file, apply your
 * changes to settings.php (which must also return an array of overrides).
 */

return [
    // Site title
    'site.title' => 'Our calendar',

    // Site logo (should be placed in public/img). Optional
    'site.logo' => 'agendav_100transp.png',

    // Site favicon (should be placed in public/img). Optional
    'site.favicon' => 'favicon.ico',

    // Site footer. Optional
    'site.footer' => 'AgenDAV ' . \AgenDAV\Version::V,

    // Base path when AgenDAV is served from a subdirectory (e.g. '/agendav').
    // Empty string means it is served at the root of the domain. Used to set
    // Slim's base path (routing + url_for) and to prefix generated asset URLs.
    'app.base_path' => '',

    // Trusted proxy ips
    'proxies' => [],

    // Database settings
    'db.options' => [
        'dbname' => 'agendav',
        'user' => 'root',
        'password' => '',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ],

    // CSRF secret — REQUIRED. Override in settings.php with a per-installation
    // random string. Generate with: php -r 'echo bin2hex(random_bytes(32))."\n";'
    // 'csrf.secret' => 'change-me',

    // Languages
    'languages' => require __DIR__ . '/languages.php',

    // Maps AgenDAV locale codes to FullCalendar locale file names
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
        'zh_CN' => 'zh-cn',
    ],

    // Twig templates path
    'twig.path' => [__DIR__ . '/../resources/private/templates'],

    // Twig options (cache disabled in dev.php)
    'twig.options' => ['cache' => __DIR__ . '/../var/cache/twig'],

    // Log path
    'log.path' => __DIR__ . '/../var/log/',

    // Log file. Empty = auto-generate dated file inside log.path.
    // Set to 'php://stdout' to send logs to standard output in containers.
    'log.file' => '',

    // Logging level
    'log.level' => 'INFO',

    // Base URL
    'caldav.baseurl' => 'http://localhost:81/',

    // Authentication method required by CalDAV server (basic or digest)
    'caldav.authmethod' => 'basic',

    // Whether to show public CalDAV urls
    'caldav.publicurls' => true,

    // Public CalDAV URL shown to users
    'caldav.baseurl.public' => 'https://caldav.server.com',

    // Connection timeout for CalDAV requests (default: wait forever)
    'caldav.connect.timeout' => 0,

    // Response timeout for CalDAV requests (default: wait forever)
    'caldav.response.timeout' => 0,

    // Whether to verify the SSL certificate (default: true)
    'caldav.certificate.verify' => true,

    // Email attribute name
    'principal.email.attribute' => '{DAV:}email',

    // Calendar sharing
    'calendar.sharing' => false,

    // Calendar sharing permissions. In case of doubt, do not modify them
    // These defaults are only useful for DAViCal (http://wiki.davical.org/index.php/Permissions)
    'calendar.sharing.permissions' => [
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
            '{DAV:}unbind',
        ],
        'read-only' => ['{DAV:}read', '{urn:ietf:params:xml:ns:caldav}read-free-busy'],
        'read-write' => ['{DAV:}read', '{DAV:}write', '{urn:ietf:params:xml:ns:caldav}read-free-busy'],
        'default' => ['{urn:ietf:params:xml:ns:caldav}read-free-busy'],
    ],

    // Default timezone
    'defaults.timezone' => 'Europe/Madrid',

    // Default language
    'defaults.language' => 'en',

    // Default time format. Options: '12' / '24'
    'defaults.time_format' => '24',

    // Default date format. Options: ymd, dmy, mdy
    'defaults.date_format' => 'ymd',

    // Default first day of week. Options: 0 (Sunday), 1 (Monday)
    'defaults.weekstart' => 0,

    // Default for showing the week numbers. Options: true/false
    'defaults.show_week_nb' => false,

    // Default for showing the "now" indicator
    'defaults.show_now_indicator' => true,

    // Default number of days covered by the "list" (agenda) view. Allowed values: 7, 14 or 31
    'defaults.list_days' => 7,

    // Default view (month, week, day or list)
    'defaults.default_view' => 'month',

    // Logout redirection. Optional
    'logout.redirection' => '',

    // Calendar colors (hex codes)
    // @deprecated Bare hex without '#' (e.g. '03A9F4') is still accepted but deprecated
    'calendar.colors' => [
        '#03A9F4', '#3F51B5', '#F44336', '#E91E63', '#9C27B0', '#673AB7',
        '#B3E5FC', '#C5CAE9', '#FFCDD2', '#F8BBD0', '#E1BEE7', '#D1C4E9',
        '#4CAF50', '#FFC107', '#CDDC39', '#FF9800', '#795548', '#9E9E9E',
        '#C8E6C9', '#FFF9C4', '#F0F4C3', '#FFE0B2', '#D7CCC8', '#F5F5F5',
    ],

    // Additional authentication methods (FQCNs implementing AuthenticationMethodInterface)
    'auth.methods' => [],

    // HTTP debug logging
    'http.debug' => false,

    // Session storage backend. 'pdo' uses the database (default); 'native' uses
    // PHP file sessions (useful on hosts where MariaDB GET_LOCK() is unavailable).
    'session.handler' => 'pdo',

    // Doctrine ORM metadata/query cache driver. 'filesystem' (default) writes to
    // var/cache/. Set to 'redis' when a Redis server is reachable at orm.cache.redis.dsn.
    'orm.cache' => 'filesystem',

    // Redis DSN used when orm.cache = 'redis'. Example: 'redis://localhost:6379'
    'orm.cache.redis.dsn' => 'redis://localhost:6379',
];
