<?php

use Middlewares\Honeypot;
use Psr\Log\LoggerInterface;
use Slim\App;
use TailgateWeb\Handlers\MyErrorHandler;
use TailgateWeb\Middleware\AddGlobalsToTwigMiddleware;
use TailgateWeb\Middleware\CleanStringsMiddleware;
use TailgateWeb\Middleware\TwigMiddleware;
use TailgateWeb\Session\SessionHelperInterface;

return function (App $app) {

    $container = $app->getContainer();

    // Remember LIFO!
    // last in this list is the first touched

    // variables to see in the view 
    $app->add(new AddGlobalsToTwigMiddleware($container->get(SessionHelperInterface::class), $container->get('view')));

    // further configure twig with request data
    $app->add(TwigMiddleware::createFromContainer($app));

    // trim and set "" to null
    $app->add(CleanStringsMiddleware::class);

    // CSRF
    $app->add($container->get('csrf'));

    // for forms
    $app->add(new Honeypot());

    // add error middleware last
    $settings = $container->get('settings')['errorHandlerMiddleware'];
    $displayErrorDetails = $settings['displayErrorDetails'];
    $logErrors = $settings['logErrors'];
    $logErrorDetails = $settings['logErrorDetails'];

    $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);

    // use my custom error handler
    $myErrorHandler = new MyErrorHandler(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        $container->get(LoggerInterface::class)
    );
    $errorMiddleware->setDefaultErrorHandler($myErrorHandler);

    $app->add($errorMiddleware);
};
