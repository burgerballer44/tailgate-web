<?php

use Dotenv\Dotenv;
use Monolog\Logger;
use Slim\App;

return function (App $app) {

    $container = $app->getContainer();

    // load environment variables from .env file
    $dotenv = Dotenv::create(dirname(__DIR__));
    $dotenv->load();
    $dotenv->required([
        'DISPLAY_ERROR_DETAILS',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'API',
        'CLIENT_ID',
        'CLIENT_SECRET',
    ]);

    // all custom settings the app uses should placed here
    $container->set('settings', [

        // should errors be displayed
        'displayErrorDetails' => filter_var(getenv('DISPLAY_ERROR_DETAILS'), FILTER_VALIDATE_BOOLEAN),

        // how long the sessions lasts in seconds
        'lifetime' => 10800, // 3 hours

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
            'path' => __DIR__ . '/../logs/app.log',
            'level' => Logger::DEBUG,
        ],

        // mailgun for mail
        'mailgun_api_key' => getenv('MAILGUN_API_KEY'),
        'mailgun_domain' => getenv('MAILGUN_DOMAIN'),
        'mailgun_test_mode' => true, // true means the mail gets sent to mailgun but not sent to user

    ]);
};
