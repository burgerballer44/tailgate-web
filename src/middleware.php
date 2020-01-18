<?php

use Middlewares\Honeypot;
use Psr\Log\LoggerInterface;
use Slim\App;
use TailgateWeb\Handlers\MyErrorHandler;
use TailgateWeb\Handlers\MyHtmlErrorRenderer;
use TailgateWeb\Middleware\AddGlobalsToTwigMiddleware;
use TailgateWeb\Middleware\CleanStringsMiddleware;
use TailgateWeb\Middleware\TwigMiddleware;
use TailgateWeb\Middleware\UpdateUserSessionMiddleware;
use TailgateWeb\Session\SessionHelperInterface;

return function (App $app) {

    $container = $app->getContainer();

    // Remember LIFO!
    // last in this list is the first touched

    // set user data
    $app->add(UpdateUserSessionMiddleware::class);

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
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->registerErrorRenderer('text/html', MyHtmlErrorRenderer::class);

    $app->add($errorMiddleware);
};
