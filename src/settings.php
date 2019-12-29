<?php

use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    $today = (new \DateTime())->format('Y-m-d');

    // all custom settings the app uses should placed here
    $containerBuilder->addDefinitions([
        'settings' => [

            'errorHandlerMiddleware' => [
                'displayErrorDetails' => filter_var(getenv('DISPLAY_ERROR_DETAILS'), FILTER_VALIDATE_BOOLEAN),
                'logErrors' => filter_var(getenv('LOG_ERRORS'), FILTER_VALIDATE_BOOLEAN),
                'logErrorDetails' => filter_var(getenv('LOG_ERRORS'), FILTER_VALIDATE_BOOLEAN),
            ],

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
        ]
    ]);
};
