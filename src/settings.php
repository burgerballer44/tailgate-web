<?php

use Monolog\Logger;
use Slim\App;

return function (App $app) {

    $container = $app->getContainer();

    $today = (new \DateTime())->format('Y-m-d');

    // all custom settings the app uses should placed here
    $container->set('settings', [

        // should errors be displayed
        'displayErrorDetails' => filter_var(getenv('DISPLAY_ERROR_DETAILS'), FILTER_VALIDATE_BOOLEAN),

        // how long the sessions lasts in seconds
        'lifetime' => 28800, // 8 hours

        // where the session data saves
        'session_path' => __DIR__ . '/../var/sessions/', 

        // pdo connection to database
        'pdo' => [
            'connection' => getenv('DB_CONNECTION'),
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT'),
            'database' => getenv('DB_DATABASE'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
        ],

        // API credentials
        'api' => getenv('API'),
        'client_id' => getenv('CLIENT_ID'),
        'client_secret' => getenv('CLIENT_SECRET'),

        // monolog logger 
        'logger' => [
            'name' => 'tailgate-web',
            'path' => __DIR__ . "/../var/logs/app-{$today}.log",
            'level' => Logger::DEBUG,
        ],

        // mailgun for mail
        'mailgun_api_key' => getenv('MAILGUN_API_KEY'),
        'mailgun_domain' => getenv('MAILGUN_DOMAIN'),
        'mailgun_test_mode' => DEV_MODE, // true means the mail gets sent to mailgun but NOT sent to user

    ]);
};
