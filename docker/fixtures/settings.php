<?php
return [
    'db.options' => [
        'dbname' => 'agendav',
        'user' => 'agendav',
        'password' => 'agendav',
        'host' => 'db',
        'driver' => 'pdo_mysql',
    ],

    'csrf.secret' => 'lkjihgfedcba',

    'log.path' => __DIR__ . '/../var/log/',

    'caldav.baseurl' => 'http://baikal/dav.php/',

    'caldav.authmethod' => 'basic',

    'auth.methods' => [\AgenDAV\Authentication\HttpBasic::class],
];
