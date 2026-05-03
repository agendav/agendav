<?php

use AgenDAV\Middleware\AuthMiddleware;
use AgenDAV\Middleware\CsrfMiddleware;
use AgenDAV\Middleware\TwigGlobalsMiddleware;
use DI\Bridge\Slim\Bridge as SlimBridge;
use DI\ContainerBuilder;
use Slim\Interfaces\RouteParserInterface;

// Vendor directory for Composer
$vendor_directory = getenv('COMPOSER_VENDOR_DIR');
if ($vendor_directory === false) {
    $vendor_directory = __DIR__ . '/../vendor';
}
require_once $vendor_directory . '/autoload.php';

// Available environments: prod, dev
$environment = getenv('AGENDAV_ENVIRONMENT');
if ($environment !== 'prod' && $environment !== 'dev') {
    $environment = 'prod';
}

if ($environment === 'prod') {
    ini_set('display_errors', '0');
}

// Sanity check: a settings.php file must exist next to default.settings.php
if (!file_exists(__DIR__ . '/../config/settings.php')) {
    echo 'settings.php file not found';
    exit(255);
}

// Build the DI container by stacking definition files (later ones override earlier)
$builder = new ContainerBuilder();
$builder->useAutowiring(true);
$builder->addDefinitions(__DIR__ . '/../config/default.settings.php');
$builder->addDefinitions(__DIR__ . '/../config/settings.php');
$builder->addDefinitions(__DIR__ . '/../config/' . $environment . '.php');
$builder->addDefinitions(__DIR__ . '/../app/services.php');
$builder->addDefinitions(['environment' => $environment]);
$container = $builder->build();

// Build the Slim app via the PHP-DI bridge so controllers can be autowired
$app = SlimBridge::create($container);

// Make the RouteParser available to Twig (for url_for) and to AuthMiddleware
$container->set(RouteParserInterface::class, $app->getRouteCollector()->getRouteParser());

// Register routes
(require __DIR__ . '/../app/routes.php')($app);

$app->add(new CsrfMiddleware($container));

// Routing + error handling come last so they run first
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(
    (bool) ($container->get('debug') ?? false),
    true,
    true,
    $container->get('monolog')
);
$errorMiddleware->setDefaultErrorHandler(new \AgenDAV\ErrorHandler($container));

// TwigGlobalsMiddleware must wrap the error middleware: a routing failure
// throws before any inner middleware runs, and the error template references
// these globals (title, lang, favicon, ...).
$app->add(new TwigGlobalsMiddleware($container));

$app->run();
