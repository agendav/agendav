<?php

/*
 * Copyright 2012 Jorge López Pérez <jorge@adobo.org>
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

use \AgenDAV\Data\Permissions;
use \AgenDAV\Data\SinglePermission;

class MY_Controller extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->container = new Pimple();

        /*
         * Make some CI models/libraries available to Pimple.
         * PHP 5.3 doesn't support the use of $this inside closures
         */
        $ci_logger = $this->log;
        $ci_shared_calendars = $this->shared_calendars;
        $enable_calendar_sharing = $this->config->item('enable_calendar_sharing');


        // Database connection
        $db_options = $this->config->item('db');
        $this->container['db'] = $this->container->share(function($container) use ($db_options) {
            $db = new \AgenDAV\DB($db_options);

            return $db->getConnection();
        });

        // ORM Entity manager
        $this->container['entity_manager'] = $this->container->share(function($container) use ($db_options) {
            $setup = Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
                array(__DIR__ . '/../../lib/AgenDAV/Data')
            );

            return Doctrine\ORM\EntityManager::create($db_options, $setup);
        });

        // Fractal manager
        $this->container['fractal'] = $this->container->share(function($container) {
            $fractal = new League\Fractal\Manager();
            $fractal->setSerializer(new League\Fractal\Serializer\JsonApiSerializer());

            return $fractal;
        });

        // Preferences repository
        $this->container['preferences_repository'] = $this->container->share(function($container) {
            $em = $container['entity_manager'];
            return new AgenDAV\Repositories\DoctrineOrmPreferencesRepository($em);
        });

        // URL generator
        $cfg = array(
            'caldav_base_url' => $this->config->item('caldav_base_url'),
            'caldav_principal_template' => $this->config->item('caldav_principal_template'),
            'caldav_calendar_homeset_template' => $this->config->item('caldav_calendar_homeset_template'),
            'caldav_public_base_url' => $this->config->item('caldav_public_base_url')
        );

        $this->container['urlgenerator'] = $this->container->share(function($container) use ($cfg){
            return new \AgenDAV\URL(
                $cfg['caldav_base_url'],
                $cfg['caldav_principal_template'],
                $cfg['caldav_calendar_homeset_template'],
                $cfg['caldav_public_base_url']
            );
        });

        // Encryption
        $encryption_key = $this->config->item('encryption_key');
        $this->container['encryptor'] = $this->container->share(function($container) use ($encryption_key) {
            $encryption_key = substr(sha1($encryption_key), 0, 16); // Use AES128
            $source_encryptor = new \Keboola\Encryption\AesEncryptor($encryption_key);

            return new \AgenDAV\Encryption\KeboolaAesEncryptor($source_encryptor);
        });

        // Session stuff
        $session_options = $this->config->item('sessions');
        $this->container['session_handler'] = $this->container->share(function($container) {
            $db = $container['db'];
            $encryptor = $container['encryptor'];
            $dbal_handler = new \Symfony\Bridge\Doctrine\HttpFoundation\DbalSessionHandler($db);

            return new \AgenDAV\Session\SessionEncrypter($dbal_handler, $encryptor);
        });

        $this->container['session_storage'] = $this->container->share(function($container) use ($session_options) {
            $storage = new Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage(
                $session_options,
                $container['session_handler']
            );

            return $storage;
        });

        $this->container['session'] = $this->container->share(function($container) {
            return new \AgenDAV\Session\HttpFoundationSession($container['session_storage']);
        });

        $this->container['session']->initialize();

        // HTTP connection logger
        $log_path = $this->config->item('log_path');
        $this->container['http_logger'] = $this->container->share(function($container) use ($log_path) {
            $logger = new \Monolog\Logger('http');
            $handler = new \Monolog\Handler\StreamHandler($log_path . 'http.log', \Monolog\Logger::DEBUG);
            $formatter = new \Monolog\Formatter\LineFormatter(
                "[%datetime%] %context% %extra% %message%\n",
                null,                                           // Default date format
                true,                                           // Allow line breaks
                true                                            // Ignore empty contexts/extra
            );
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
            $logger->pushProcessor(new \Monolog\Processor\WebProcessor);

            return $logger;
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

        // Sharing support enabled
        if ($enable_calendar_sharing === true) {

            // Shares repository
            $this->container['shares_repository'] = $this->container->share(function($container) {
                $em = $container['entity_manager'];
                return new AgenDAV\Repositories\DoctrineOrmSharesRepository($em);
            });

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
    }
}

