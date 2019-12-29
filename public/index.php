<?php

use DI\ContainerBuilder;
use Slim\App;
use TailgateWeb\Session\SessionStarter;

require __DIR__ . '/../vendor/autoload.php';

// set environment variables
require __DIR__ . '/../src/environment.php';

// instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// initialize session
$session = new SessionStarter([
    'name' => 'tailgate_session',
    'secure' => PROD_MODE,
    'lifetime' => 28800, // how long the sessions lasts in seconds
    'session_path' => __DIR__ . '/../var/sessions/', // where the session data saves
]);

if (PROD_MODE) {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache/container');
}

// add settings to the app
(require __DIR__ . '/../src/settings.php')($containerBuilder);

// configure dependencies the application needs
(require __DIR__ . '/../src/dependencies.php')($containerBuilder);

// build PHP-DI Container instance
$container = $containerBuilder->build();

// create app instance
$app = $container->get(App::class);

// register middleware that every request needs
(require __DIR__ . '/../src/middleware.php')($app);

// register routes the application uses
(require __DIR__ . '/../src/routes.php')($app);

// run app
$app->run();