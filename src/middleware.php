<?php

use Middlewares\Honeypot;
use Slim\App;
use TailgateWeb\Middleware\AddGlobalsToTwigMiddleware;
use TailgateWeb\Middleware\CleanStringsMiddleware;

return function (App $app) {

    $container = $app->getContainer();

    // Remember LIFO!
    // last in this list is the first touched

    // variables to see in the view 
    $app->add(new AddGlobalsToTwigMiddleware($container->get('session'), $container->get('view')));

    // trim and set "" to null
    $app->add(new CleanStringsMiddleware());

    // CSRF
    $app->add($container->get('csrf'));

    // for forms
    $app->add(new Honeypot());
};
