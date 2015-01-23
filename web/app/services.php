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

    return Doctrine\ORM\EntityManager::create($app['db.settings'], $setup);
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
    return new AgenDAV\Repositories\DoctrineOrmPreferencesRepository($em);
});

// Encryption
$app['encryptor'] = $app->share(function($app) {
    $encryption_key = substr(sha1($app['encryption.key']), 0, 16); // Use AES128
    $source_encryptor = new \Keboola\Encryption\AesEncryptor($encryption_key);

    return new \AgenDAV\Encryption\KeboolaAesEncryptor($source_encryptor);
});

// Sessions
$app['session.storage.handler'] = $app->share(function($app) {
    $db = $app['db'];
    $encryptor = $app['encryptor'];
    $dbal_handler = new \Symfony\Bridge\Doctrine\HttpFoundation\DbalSessionHandler($db);

    return new \AgenDAV\Session\SessionEncrypter($dbal_handler, $encryptor);
});

/*

// HTTP connection logger
$log_path = $this->config->item('log_path');
$this->container['http_logger'] = $this->container->share(function($container) use ($log_path) {
    return \AgenDAV\Log::generateHttpLogger($log_path);
});

// Guzzle HTTP client
$config_guzzle = [
    'base_url' => $this->config->item('caldav_base_url'),
];
$enable_http_logging = $this->config->item('enable_http_logging');
$this->container['guzzle_http'] = $this->container->share(function($container) use ($config_guzzle, $enable_http_logging) {
    $client = new \GuzzleHttp\Client($config_guzzle);

    if ($enable_http_logging === true) {
        $log_subscriber = new GuzzleHttp\Subscriber\Log\LogSubscriber(
            $container['http_logger'],
            \GuzzleHttp\Subscriber\Log\Formatter::DEBUG
        );
        $client->getEmitter()->attach($log_subscriber);
    }

    return $client;
});

// AgenDAV HTTP client, based on Guzzle
$auth_type = $this->config->item('caldav_http_auth_method');
$this->container['http_client'] = $this->container->share(function($container) use ($auth_type) {
    return \AgenDAV\Http\ClientFactory::create(
        $container['guzzle_http'],
        $container['session'],
        $auth_type
    );
});

// XML generator
$this->container['xml_generator'] = $this->container->share(function($container) {
    return new \AgenDAV\XML\Generator();
});

// XML parser
$this->container['xml_parser'] = $this->container->share(function($container) {
    return new \AgenDAV\XML\Parser();
});

// XML toolkit
$this->container['xml_toolkit'] = $this->container->share(function($container) {
    return new \AgenDAV\XML\Toolkit(
        $container['xml_parser'],
        $container['xml_generator']
    );
});

// Event parser
$this->container['event_parser'] = $this->container->share(function($container) {
    return new \AgenDAV\Event\Parser\VObjectParser;
});

// CalDAV client
$this->container['caldav_client'] = $this->container->share(function($container) {
    return new \AgenDAV\CalDAV\Client(
        $container['http_client'],
        $container['xml_toolkit'],
        $container['event_parser']
    );
});

// Calendar finder
$this->container['calendar_finder'] = $this->container->share(function($container) {
    return new \AgenDAV\CalendarFinder(
        $container['session'],
        $container['caldav_client']
    );
});

// Event builder
// TODO custom timezone
$default_timezone = new \DateTimeZone($this->config->item('default_timezone'));
$this->container['event_builder'] = $this->container->share(function($container) use ($default_timezone) {
    return new \AgenDAV\Event\Builder\VObjectBuilder($default_timezone);
});

// Sharing support enabled
if ($enable_calendar_sharing === true) {

    // Shares repository
    $this->container['shares_repository'] = $this->container->share(function($container) {
        $em = $container['orm'];
        return new AgenDAV\Repositories\DoctrineOrmSharesRepository($em);
    });

    // Add the shares repository to the calendar finder service
    $this->container['calendar_finder']->setSharesRepository(
        $this->container['shares_repository']
    );

    // Privileges and permissions configuration
    $cfg_permissions = $this->config->item('permissions');
    $this->container['permissions'] = $this->container->share(
        function($container) use ($cfg_permissions) {
            return new \AgenDAV\CalDAV\Share\Permissions(
                $cfg_permissions
            );
        }
    );

    // ACL objects
    $this->container['acl'] = function($c) {
        return new \AgenDAV\CalDAV\Share\ACL(
            $c['permissions']
        );
    };
}
 */
