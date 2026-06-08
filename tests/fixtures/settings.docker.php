<?php
// Docker dev stack settings. Copied to config/settings.php by smoke-test.sh
// and reset-events.sh. Not for production use.
return [
    'db.options' => [
        'dbname'   => 'agendav',
        'user'     => 'agendav',
        'password' => 'agendav',
        'host'     => 'db',
        'driver'   => 'pdo_mysql',
        'charset'  => 'utf8mb4',
    ],

    'caldav.baseurl'        => 'http://baikal/dav.php/',
    'caldav.baseurl.public' => 'http://localhost:8081/dav.php/',
    'caldav.publicurls'     => true,
    'caldav.authmethod'     => 'basic',

    'csrf.secret'            => 'docker-dev-csrf-secret-do-not-use-in-production',
    'session.encryption.key' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
    'log.level'              => 'DEBUG',

    'site.title' => 'AgenDAV (docker)',

    'calendar.subscriptions' => true,

    // Enable HTTP Basic auth as an alternative to the form login
    'auth.methods' => [\AgenDAV\Authentication\HttpBasic::class],
];
