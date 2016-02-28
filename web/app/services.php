<?php

/*
 * Copyright 2015 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

// ORM Entity manager
$app['orm'] = $app->share(function($app) {
    $setup = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
        [ __DIR__ . '/../src/Data' ]
    );

    return Doctrine\ORM\EntityManager::create($app['db.options'], $setup);
});

// Fractal manager
$app['fractal'] = $app->share(function($app) {
    $fractal = new League\Fractal\Manager();
    $fractal->setSerializer(new League\Fractal\Serializer\JsonApiSerializer());

    return $fractal;
});

// Preferences repository
$app['preferences.repository'] = $app->share(function($app) {
    $em = $app['orm'];
    $repository = new AgenDAV\Repositories\DoctrineOrmPreferencesRepository($em);

    // Default values
    $repository->setDefaults([
        'language' => $app['defaults.language'],
        'default_calendar' => null,
        'hidden_calendars' => [],
        'time_format' => $app['defaults.time_format'],
        'date_format' => $app['defaults.date_format'],
        'weekstart' => $app['defaults.weekstart'],
        'timezone' => $app['defaults.timezone'],
    ]);

    return $repository;
});

// Principals repository (queries the CalDAV server)
$app['principals.repository'] = $app->share(function($app) {
    $repository = new AgenDAV\Repositories\DAVPrincipalsRepository(
        $app['xml.toolkit'],
        $app['caldav.client'],
        $app['principal.email.attribute']
    );

    return $repository;
});


// Encryption
$app['encryptor'] = $app->share(function($app) {
    $encryption_key = $app['encryption.key'];
    $source_encryptor = new phpseclib\Crypt\AES();

    return new \AgenDAV\Encryption\PhpSecLibAesEncryptor(
        $source_encryptor,
        $encryption_key
    );
});

// Sessions handler
$app['session.storage.handler'] = $app->share(function($app) {
    $encryptor = $app['encryptor'];
    $pdo_handler = new Symfony\Component\HttpFoundation\Session\Storage\Handler\LegacyPdoSessionHandler(
        $app['db']->getWrappedConnection(),
        [
            'db_table' => 'sessions',
            'db_id_col'     => 'sess_id',
            'db_data_col'   => 'sess_data',
            'db_time_col'   => 'sess_time',
        ],
        $app['session.storage.options']
    );

    // We use a proxy that encrypts all session data
    return new \AgenDAV\Session\SessionEncrypter($pdo_handler, $encryptor);
});


// HTTP connection logger
$app['monolog.http'] = $app->share(function($app) {
    return \AgenDAV\Log::generateHttpLogger($app['log.path']);
});

// Guzzle HTTP client
$app['guzzle'] = $app->share(function($app) {
    $client = new \GuzzleHttp\Client([
        'base_url' => $app['caldav.baseurl'],
    ]);

    if (isset($app['http.debug']) && $app['http.debug'] === true) {
        $log_subscriber = new GuzzleHttp\Subscriber\Log\LogSubscriber(
            $app['monolog.http'],
            "\n{request}\n~~~~~~~~~~~~\n\n{response}\n~~~~~~~~~~~~\nError?: {error}\n"
        );
        $client->getEmitter()->attach($log_subscriber);
    }

    return $client;
});

// AgenDAV HTTP client, based on Guzzle
$app['http.client'] = $app->share(function($app) {
    return \AgenDAV\Http\ClientFactory::create(
        $app['guzzle'],
        $app['session'],
        $app['caldav.authmethod']
    );
});

// XML generator
$app['xml.generator'] = $app->share(function($app) {
    return new \AgenDAV\XML\Generator();
});

// XML parser
$app['xml.parser'] = $app->share(function($app) {
    return new \AgenDAV\XML\Parser();
});

// XML toolkit
$app['xml.toolkit'] = $app->share(function($app) {
    return new \AgenDAV\XML\Toolkit(
        $app['xml.parser'],
        $app['xml.generator']
    );
});

// Event parser
$app['event.parser'] = $app->share(function($app) {
    return new \AgenDAV\Event\Parser\VObjectParser;
});

// CalDAV client
$app['caldav.client'] = $app->share(function($app) {
    return new \AgenDAV\CalDAV\Client(
        $app['http.client'],
        $app['xml.toolkit'],
        $app['event.parser']
    );
});

// Calendar finder
$app['calendar.finder'] = $app->share(function($app) {

    $finder = new \AgenDAV\CalendarFinder(
        $app['session'],
        $app['caldav.client']
    );

    // Add the shares repository to the calendar finder service
    if ($app['calendar.sharing']=== true) {
        $finder->setSharesRepository($app['shares_repository']);
    }


    return $finder;
});

// Event builder
$app['event.builder'] = $app->share(function($app) {
    $timezone = new \DateTimeZone($app['user.timezone']);
    return new \AgenDAV\Event\Builder\VObjectBuilder($timezone);
});


// CSRF manager that stores tokens inside sessions
$app['csrf.manager'] = $app->share(function ($app) {
    $storage = new Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage($app['session']);
    return new Symfony\Component\Security\Csrf\CsrfTokenManager(null, $storage);
});


// Sharing support enabled
if ($app['calendar.sharing'] === true) {

    // Shares repository
    $app['shares_repository'] = $app->share(function($app) {
        $em = $app['orm'];
        return new AgenDAV\Repositories\DoctrineOrmSharesRepository($em);
    });

    // Configured permissions
    $app['permissions'] = $app->share(function($app) {
        return new \AgenDAV\CalDAV\Share\Permissions($app['calendar.sharing.permissions']);
    });

    // ACL factory
    $app['acl'] = function($app) {
        return new \AgenDAV\CalDAV\Share\ACL($app['permissions']);
    };
}
