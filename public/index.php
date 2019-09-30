<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Middleware\ErrorMiddleware;
use Slim\ResponseEmitter;
use TailgateWeb\Session\SessionStarter;

require __DIR__ . '/../vendor/autoload.php';

// set environment variables
require __DIR__ . '/../src/environment.php';

// instantiate PHP-DI Container
$container = new Container();

// set the container we want to use and instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();

// add settings to the app
$settings = require __DIR__ . '/../src/settings.php';
$settings($app);

// initialize session
$session = new SessionStarter([
    'name' => 'tailgate_session',
    'secure' => PROD_MODE,
    'lifetime' => $container->get('settings')['lifetime'],
    'session_path' => realpath($container->get('settings')['session_path']),
]);

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