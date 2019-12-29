<?php

use Dotenv\Dotenv;

// load environment variables from .env file
$dotenv = Dotenv::create(dirname(__DIR__));
$dotenv->load();
$dotenv->required([
    'MODE',
    'DISPLAY_ERROR_DETAILS',
    'LOG_ERRORS',
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

// set constants based on the environemnt we want
// should be 'dev' or 'prod'
$mode = getenv('MODE');
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