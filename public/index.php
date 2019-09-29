<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Middleware\ErrorMiddleware;
use Slim\ResponseEmitter;
use TailgateWeb\Session\Session;

require __DIR__ . '/../vendor/autoload.php';

// set environment variables
require __DIR__ . '/../src/environment.php';

// instantiate PHP-DI Container
$containerBuilder = new \DI\ContainerBuilder();
if (PROD_MODE) {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache/');
}
$container = $containerBuilder->build();

// set the container we want to use and instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();

// add settings to the app
$settings = require __DIR__ . '/../src/settings.php';
$settings($app);

// session initialized
$session = Session::startSession(['lifetime' => $container->get('settings')['lifetime']]);
$container->set('session', function () use ($session) {
    return $session;
});

// create the request
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// configure dependencies the application needs
$dependencies = require __DIR__ . '/../src/dependencies.php';
$dependencies($app);

// register middleware that every request needs
$middleware = require __DIR__ . '/../src/middleware.php';
$middleware($app);

// register routes the application uses
$routes = require __DIR__ . '/../src/routes.php';
$routes($app);

// add the final middleware that handles errors
$callableResolver = $app->getCallableResolver();
$responseFactory = $app->getResponseFactory();
$errorMiddleware = new ErrorMiddleware(
    $callableResolver,
    $responseFactory, 
    $container->get('settings')['displayErrorDetails'],
    false,
    false
);
$app->add($errorMiddleware);

// run app and emit response
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);