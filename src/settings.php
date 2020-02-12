<?php

use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    $today = (new \DateTime())->format('Y-m-d');

    // all custom settings the app uses should placed here
    $containerBuilder->addDefinitions([
        'settings' => [

            'errorHandlerMiddleware' => [
                'displayErrorDetails' => filter_var(getenv('WEB_DISPLAY_ERROR_DETAILS'), FILTER_VALIDATE_BOOLEAN),
                'logErrors' => filter_var(getenv('WEB_LOG_ERRORS'), FILTER_VALIDATE_BOOLEAN),
                'logErrorDetails' => filter_var(getenv('WEB_LOG_ERRORS'), FILTER_VALIDATE_BOOLEAN),
            ],

            // pdo connection to database
            'pdo' => [
                'connection' => getenv('WEB_DB_CONNECTION'),
                'host' => getenv('WEB_DB_HOST'),
                'port' => getenv('WEB_DB_PORT'),
                'database' => getenv('WEB_DB_DATABASE'),
                'username' => getenv('WEB_DB_USERNAME'),
                'password' => getenv('WEB_DB_PASSWORD'),
            ],

            // API credentials
            'api' => getenv('WEB_API'),
            'client_id' => getenv('WEB_CLIENT_ID'),
            'client_secret' => getenv('WEB_CLIENT_SECRET'),

            // monolog logger 
            'logger' => [
                'name' => 'tailgate-web',
                'path' => __DIR__ . "/../var/logs/app-{$today}.log",
                'level' => Logger::DEBUG,
            ],

            // mailgun for mail
            'mailgun_api_key' => getenv('WEB_MAILGUN_API_KEY'),
            'mailgun_domain' => getenv('WEB_MAILGUN_DOMAIN'),
            'mailgun_test_mode' => filter_var(getenv('WEB_SEND_TEST_EMAILS'), FILTER_VALIDATE_BOOLEAN), // true means the mail gets sent to mailgun but NOT sent to user
        ]
    ]);
};
