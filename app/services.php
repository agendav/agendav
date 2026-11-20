<?php

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
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

use Psr\Container\ContainerInterface;

return [
    // Default environment (overridden in index.php)
    'environment' => 'prod',

    // Translator locale (depends on user preferences; updated per request)
    'locale' => fn (ContainerInterface $c) => $c->get('defaults.language'),

    // Per-request user context. AuthMiddleware populates the singleton.
    \AgenDAV\UserContext::class => \DI\create(\AgenDAV\UserContext::class),

    // Symfony Session Handler: 'pdo' (default) uses DB-backed sessions, 'native' falls back to PHP file sessions
    'session' => function (ContainerInterface $c) {
        $handler = $c->get('session.handler') === 'native'
            ? null
            : $c->get('session.storage.handler');
        $storage = new \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage(
            $c->get('session.storage.options'),
            $handler
        );
        return new \Symfony\Component\HttpFoundation\Session\Session($storage);
    },

    'session.storage.handler' => function (ContainerInterface $c) {
        // PdoSessionHandler uses its own options (db_table, db_id_col, ...).
        // session.storage.options carries cookie_* options that belong to
        // NativeSessionStorage, so we don't forward them here.
        // lock_mode: LOCK_ADVISORY uses MySQL GET_LOCK() instead of a
        // transaction so the session connection doesn't collide with
        // Doctrine ORM (which begins its own transaction on flush()).
        return new \Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler(
            $c->get('db')->getNativeConnection(),
            ['lock_mode' => \Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler::LOCK_ADVISORY]
        );
    },

    // Doctrine DBAL connection
    'db' => function (ContainerInterface $c) {
        $config = new \Doctrine\DBAL\Configuration();
        return \Doctrine\DBAL\DriverManager::getConnection($c->get('db.options'), $config);
    },

    // Doctrine ORM EntityManager
    'orm' => function (ContainerInterface $c) {
        $development_mode = ($c->get('environment') === 'dev');

        if ($c->get('orm.cache') === 'redis') {
            $redis = \Symfony\Component\Cache\Adapter\RedisAdapter::createConnection($c->get('orm.cache.redis.dsn'));
            $cache = new \Symfony\Component\Cache\Adapter\RedisAdapter($redis, 'doctrine');
        } else {
            $cache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter('doctrine', 0, __DIR__ . '/../var/cache');
        }

        $config = \Doctrine\ORM\ORMSetup::createAttributeMetadataConfiguration(
            [__DIR__ . '/../src/Data'],
            $development_mode,
            null,
            $cache
        );

        return new \Doctrine\ORM\EntityManager($c->get('db'), $config);
    },

    // Monolog logger
    'monolog' => function (ContainerInterface $c) {
        $logger = new \Monolog\Logger('agendav');
        $log_level = constant('\\Monolog\\Logger::' . strtoupper($c->get('log.level')));
        $log_file = $c->get('log.file');
        if ($log_file === '') {
            $log_dir = rtrim($c->get('log.path'), '/');
            if (!is_dir($log_dir) && !mkdir($log_dir, 0750, true) && !is_dir($log_dir)) {
                throw new \RuntimeException('Cannot create log directory: ' . $log_dir);
            }
            $log_file = $log_dir . '/' . date('Y-m-d') . '.log';
        }
        $handler = new \Monolog\Handler\StreamHandler($log_file, $log_level);
        $logger->pushHandler($handler);
        return $logger;
    },

    // HTTP traffic logger (used by Guzzle middleware in dev)
    'monolog.http' => function (ContainerInterface $c) {
        $log_file = $c->get('log.file');
        if ($log_file === '') {
            $log_file = rtrim($c->get('log.path'), '/') . '/http-' . date('Y-m-d') . '.log';
        }
        return \AgenDAV\Log::generateHttpLogger($log_file);
    },

    // Translator
    'translator' => function (ContainerInterface $c) {
        $translator = new \Symfony\Component\Translation\Translator(
            $c->get('defaults.language'),
            null,
            null,
            $c->get('debug') ?? false
        );
        $translator->setFallbackLocales(['en']);
        $translator->addLoader('php', new \Symfony\Component\Translation\Loader\PhpFileLoader());

        $languages = array_keys($c->get('languages'));
        foreach ($languages as $language) {
            $translator->addResource('php', __DIR__ . '/../resources/private/lang/' . $language . '.php', $language);
        }

        return $translator;
    },

    // Twig environment
    'twig' => function (ContainerInterface $c) {
        $loader = new \Twig\Loader\FilesystemLoader($c->get('twig.path'));
        $env = new \Twig\Environment($loader, $c->get('twig.options'));

        // Translator integration
        $env->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($c->get('translator')));

        // {{ asset(...) }} support
        $env->addExtension(new \Symfony\Bridge\Twig\Extension\AssetExtension($c->get('asset.packages')));

        // {{ url_for('route_name', { params }) }}
        $env->addExtension(new \AgenDAV\Twig\UrlForExtension($c));

        // {{ value|js_json }} — JSON encoding safe for inline <script> blocks
        $env->addExtension(new \AgenDAV\Twig\SafeJsonExtension());

        if (!empty($c->get('debug'))) {
            $env->addExtension(new \Twig\Extension\DebugExtension());
        }

        return $env;
    },

    // Symfony Asset Packages (for the asset() Twig function)
    'asset.packages' => function (ContainerInterface $c) {
        // Prefix every asset URL with the configured base path so they resolve
        // when AgenDAV is served from a subdirectory. Empty = served at root.
        $basePath = rtrim((string) ($c->has('app.base_path') ? $c->get('app.base_path') : ''), '/');
        $version = 'v' . \AgenDAV\Version::V;
        $default_package = $basePath === ''
            ? new \Symfony\Component\Asset\Package(
                new \Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy($version)
            )
            : new \Symfony\Component\Asset\PathPackage(
                $basePath,
                new \Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy($version)
            );

        $named = [
            'css' => new \Symfony\Component\Asset\PathPackage(
                $basePath . '/assets/dist/css',
                new \Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy(\AgenDAV\Version::V)
            ),
            'js' => new \Symfony\Component\Asset\PathPackage(
                $basePath . '/assets/dist/js',
                new \Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy(\AgenDAV\Version::V)
            ),
            'img' => new \Symfony\Component\Asset\PathPackage(
                $basePath . '/assets/img',
                new \Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy(\AgenDAV\Version::V)
            ),
        ];

        return new \Symfony\Component\Asset\Packages($default_package, $named);
    },

    // Password cipher used to encrypt CalDAV credentials at rest in the
    // session store. The key is read from 'session.encryption.key' (hex-
    // encoded 32 bytes) when set in settings.php, otherwise generated and
    // persisted to var/session.key on first use.
    'password.cipher' => function (ContainerInterface $c) {
        $key = null;

        if ($c->has('session.encryption.key')) {
            $hex = $c->get('session.encryption.key');
            if (is_string($hex) && $hex !== '') {
                $key = hex2bin($hex);
                if ($key === false || strlen($key) !== 32) {
                    throw new \RuntimeException(
                        "'session.encryption.key' must be a hex-encoded 32-byte string (64 hex chars)"
                    );
                }
            }
        }

        if ($key === null) {
            $keyFile = dirname(rtrim($c->get('log.path'), '/')) . '/session.key';

            if (is_readable($keyFile)) {
                $key = file_get_contents($keyFile);
                if ($key === false || strlen($key) !== 32) {
                    throw new \RuntimeException(
                        "Corrupt session encryption key file: $keyFile (delete it to force regeneration; existing sessions will be invalidated)"
                    );
                }
            } else {
                $key = random_bytes(32);
                // O_EXCL: only create if it doesn't already exist; lets concurrent workers race safely.
                $fp = @fopen($keyFile, 'xb');
                if ($fp !== false) {
                    fwrite($fp, $key);
                    fclose($fp);
                    @chmod($keyFile, 0600);
                } elseif (is_readable($keyFile)) {
                    // Someone else won the race. Read theirs.
                    $key = file_get_contents($keyFile);
                } else {
                    throw new \RuntimeException(
                        "Cannot create session encryption key file: $keyFile (check that the parent directory is writable, or set 'session.encryption.key' in settings.php)"
                    );
                }
            }
        }

        return new \AgenDAV\Session\PasswordCipher($key);
    },

    // CSRF token manager
    'csrf.manager' => function (ContainerInterface $c) {
        // Make sure the session has been started so $_SESSION is initialized
        // before NativeSessionTokenStorage touches it.
        $session = $c->get('session');
        if (!$session->isStarted()) {
            $session->start();
        }
        $storage = new \Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage();
        return new \Symfony\Component\Security\Csrf\CsrfTokenManager(null, $storage);
    },

    // Fractal manager (Note: serializer is changed per controller as needed)
    'fractal' => function (ContainerInterface $c) {
        $fractal = new \League\Fractal\Manager();
        $fractal->setSerializer(new \League\Fractal\Serializer\DataArraySerializer());
        return $fractal;
    },

    // Preferences repository
    'preferences.repository' => function (ContainerInterface $c) {
        $repository = new \AgenDAV\Repositories\DoctrineOrmPreferencesRepository($c->get('orm'));
        $repository->setDefaults([
            'language' => $c->get('defaults.language'),
            'default_calendar' => null,
            'hidden_calendars' => [],
            'time_format' => $c->get('defaults.time_format'),
            'date_format' => $c->get('defaults.date_format'),
            'weekstart' => $c->get('defaults.weekstart'),
            'timezone' => $c->get('defaults.timezone'),
            'show_week_nb' => $c->get('defaults.show_week_nb'),
            'show_now_indicator' => $c->get('defaults.show_now_indicator'),
            'list_days' => $c->get('defaults.list_days'),
            'default_view' => $c->get('defaults.default_view'),
        ]);
        return $repository;
    },

    // Principals repository
    'principals.repository' => function (ContainerInterface $c) {
        return new \AgenDAV\Repositories\DAVPrincipalsRepository(
            $c->get('xml.toolkit'),
            $c->get('caldav.client'),
            $c->get('principal.email.attribute')
        );
    },

    // Shares repository
    'shares.repository' => function (ContainerInterface $c) {
        return new \AgenDAV\Repositories\DoctrineOrmSharesRepository($c->get('orm'));
    },

    // Subscriptions repository
    'subscriptions.repository' => function (ContainerInterface $c) {
        return new \AgenDAV\Repositories\DoctrineOrmSubscriptionsRepository($c->get('orm'));
    },

    // Sharing resolver
    'sharing.resolver' => function (ContainerInterface $c) {
        return new \AgenDAV\Sharing\SharingResolver(
            $c->get('shares.repository'),
            $c->get('principals.repository')
        );
    },

    // Configured permissions
    'permissions' => function (ContainerInterface $c) {
        return new \AgenDAV\CalDAV\Share\Permissions($c->get('calendar.sharing.permissions'));
    },

    // ACL factory: each request gets a fresh ACL via php-di's factory pattern
    'acl' => \DI\factory(function (ContainerInterface $c) {
        return new \AgenDAV\CalDAV\Share\ACL($c->get('permissions'));
    }),

    // Guzzle HTTP client
    'guzzle' => function (ContainerInterface $c) {
        $stack = \GuzzleHttp\HandlerStack::create();

        if ($c->has('http.debug') && $c->get('http.debug') === true) {
            $stack->push(\GuzzleHttp\Middleware::log(
                $c->get('monolog.http'),
                new \GuzzleHttp\MessageFormatter(
                    "\n{request}\n~~~~~~~~~~~~\n\n{response}\n~~~~~~~~~~~~\nError?: {error}\n"
                )
            ));
        }

        $baseurl = $c->get('caldav.baseurl');
        $username = $c->get('session')->get('username');
        $baseurl = str_replace('%u', (string) $username, $baseurl);

        return new \GuzzleHttp\Client([
            'base_uri' => $baseurl,
            'handler' => $stack,
            'connect_timeout' => $c->get('caldav.connect.timeout'),
            'timeout' => $c->get('caldav.response.timeout'),
            'verify' => $c->get('caldav.certificate.verify'),
        ]);
    },

    // AgenDAV HTTP client
    'http.client' => function (ContainerInterface $c) {
        return \AgenDAV\Http\ClientFactory::create(
            $c->get('guzzle'),
            $c->get('session'),
            $c->get('caldav.authmethod'),
            $c->get('password.cipher')
        );
    },

    // XML
    'xml.generator' => fn (ContainerInterface $c) => new \AgenDAV\XML\Generator(),
    'xml.parser' => fn (ContainerInterface $c) => new \AgenDAV\XML\Parser(),
    'xml.toolkit' => fn (ContainerInterface $c) => new \AgenDAV\XML\Toolkit(
        $c->get('xml.parser'),
        $c->get('xml.generator')
    ),

    // Event parser
    'event.parser' => fn (ContainerInterface $c) => new \AgenDAV\Event\Parser\VObjectParser(),

    // CalDAV client
    'caldav.client' => function (ContainerInterface $c) {
        return new \AgenDAV\CalDAV\Client(
            $c->get('http.client'),
            $c->get('xml.toolkit'),
            $c->get('event.parser')
        );
    },

    // Calendar finder
    'calendar.finder' => function (ContainerInterface $c) {
        $finder = new \AgenDAV\CalendarFinder($c->get('session'), $c->get('caldav.client'));
        if ($c->get('calendar.sharing') === true) {
            $finder->setSharesRepository($c->get('shares.repository'));
        }
        $finder->setSubscriptionsRepository($c->get('subscriptions.repository'));
        return $finder;
    },

    // Event builder (depends on the user's timezone, set by AuthMiddleware via UserContext)
    'event.builder' => \DI\factory(function (ContainerInterface $c) {
        $userContext = $c->get(\AgenDAV\UserContext::class);
        $tz = $userContext->getTimezone() ?? $c->get('defaults.timezone');
        return new \AgenDAV\Event\Builder\VObjectBuilder(new \DateTimeZone($tz));
    }),
];
