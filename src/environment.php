<?php

use Dotenv\Dotenv;

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