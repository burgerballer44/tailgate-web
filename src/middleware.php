<?php

use Middlewares\Honeypot;
use Slim\App;
use TailgateWeb\Middleware\AddGlobalsToTwigMiddleware;
use TailgateWeb\Middleware\OldInputMiddleware;
use TailgateWeb\Middleware\CleanStringsMiddleware;

return function (App $app) {

    $container = $app->getContainer();

    // Remember LIFO!

    // post fields are saved for reuse
    $app->add(new OldInputMiddleware($container->get('view')));

    // variables to see in the view 
    $app->add(new AddGlobalsToTwigMiddleware($container->get('session'), $container->get('view')));

    // trim and set "" to null
    $app->add(new CleanStringsMiddleware());

    // CSRF
    $app->add($container->get('csrf'));

    // for forms
    $app->add(new Honeypot());

};
