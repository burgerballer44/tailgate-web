<?php

use Dotenv\Dotenv;

// load environment variables from .env file
$dotenv = Dotenv::create(dirname(__DIR__));
$dotenv->load();
$dotenv->required([
    'WEB_MODE',
    'WEB_DISPLAY_ERROR_DETAILS',
    'WEB_LOG_ERRORS',
    'WEB_SEND_TEST_EMAILS',
    'WEB_API',
    'WEB_CLIENT_ID',
    'WEB_CLIENT_SECRET',
    'WEB_MAILGUN_API_KEY',
    'WEB_MAILGUN_DOMAIN',
]);

// set constants based on the environemnt we want
// should be 'dev' or 'prod'
$mode = getenv('WEB_MODE');
$devMode = true;
$prodMode = false;
if ('dev' != $mode) {
    $mode = 'prod';
    $devMode = false;
    $prodMode = true;
}
define("MODE", $mode);
define("DEV_MODE", $devMode);
define("PROD_MODE", $prodMode);