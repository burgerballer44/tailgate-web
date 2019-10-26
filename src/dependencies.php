<?php

use Knlv\Slim\Views\TwigMessages;
use Mailgun\Mailgun;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use TailgateWeb\Extensions\CsrfExtension;
use TailgateWeb\Extensions\FormBuilderExtension;
use TailgateWeb\Extensions\HelperExtension;
use TailgateWeb\Extensions\HoneypotExtension;
use TailgateWeb\Middleware\CsrfMiddleware;
use TailgateWeb\Middleware\AdminMiddleware;
use TailgateWeb\Middleware\MustBeSignedInMiddleware;
use TailgateWeb\Middleware\MustBeSignedOutMiddleware;
use TailgateWeb\Middleware\ViewGlobalMiddleware;
use TailgateWeb\Session\Helper;

return function (App $app) use ($request) {

    $container = $app->getContainer();

    // wrapper for handling $_SESSION
    $container->set('session', function () {
        return new Helper;
    });

    // pdo connection to database
    $connection = $container->get('settings')['pdo']['connection'];
    $host = $container->get('settings')['pdo']['host'];
    $port = $container->get('settings')['pdo']['port'];
    $database = $container->get('settings')['pdo']['database'];
    $username = $container->get('settings')['pdo']['username'];
    $password = $container->get('settings')['pdo']['password'];

    $container->set('pdo', function ($container) use (
        $connection, $host, $port, $database, $username, $password
    ) {
        return new PDO("{$connection}:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    });

    // client to access API
    $container->set('guzzleClient', function ($container) {
        $client = new GuzzleHttp\Client([
            'base_uri' => $container->get('settings')['api'],
            'allow_redirects' => false,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        return $client;
    });

    // csrf
    $container->set('csrf', function ($container) use ($app) {
        return new CsrfMiddleware('csrf', $app->getResponseFactory());
    });

    // flash
    $container->set('flash', function () {
        return new Messages();
    });

    // view
    $container->set('view', function ($container) use ($app, $request) {
        $twig = new Twig(__DIR__ . '/../views/', [
            'cache' => PROD_MODE ?  __DIR__ . '/../var/cache/twig/' : false,
            'auto_reload' => true,
            'debug' => $container->get('settings')['displayErrorDetails'],
            'strict_variables' => $container->get('settings')['displayErrorDetails'],
        ]);

        $twig->addExtension(new TwigExtension($app->getRouteCollector()->getRouteParser(), $request->getUri(), $app->getBasePath()));
        $twig->addExtension(new HoneypotExtension());
        $twig->addExtension(new CsrfExtension($container->get('csrf')));
        $twig->addExtension(new TwigMessages($container->get('flash')));
        $twig->addExtension(new FormBuilderExtension($request->getParsedBody()));
        $twig->addExtension(new HelperExtension($container->get('session')));

        return $twig;
    });

    // other middleware that are set to individual routes
    $container->set(AdminMiddleware::class, function ($container) use ($app) {
        return new AdminMiddleware(
            $container->get('session'),
            $container->get('flash'),
            $app->getResponseFactory()
        );
    });
    $container->set(MustBeSignedInMiddleware::class, function ($container) use ($app) {
        return new MustBeSignedInMiddleware(
            $container->get('session'),
            $app->getResponseFactory()
        );
    });
    $container->set(MustBeSignedOutMiddleware::class, function ($container) use ($app) {
        return new MustBeSignedOutMiddleware(
            $container->get('session'),
            $app->getResponseFactory()
        );
    });

    // logger
    $container->set('logger', function ($container) {
        $settings = $container->get('settings');

        $loggerSettings = $settings['logger'];
        $logger = new Logger($loggerSettings['name']);

        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
        $logger->pushHandler($handler);

        return $logger;
    });

    // mailer with mailgun
    $container->set('mailer', function () use ($app, $container, $request) {

        $routeParser = $app->getRouteCollector()->getRouteParser();
        $mailgun = Mailgun::create($container->get('settings')['mailgun_api_key']);
        $domain = $container->get('settings')['mailgun_domain'];
        $uri = $request->getUri();

        return new class(
            $mailgun,
            $domain,
            $routeParser,
            $uri,
        ) {
            private $mailgun;
            private $domain;
            private $routeParser;
            private $uri;
            
            public function __construct($mailgun, $domain, $routeParser, $uri)
            {
                $this->mailgun = $mailgun;
                $this->domain = $domain;
                $this->routeParser = $routeParser;
                $this->uri = $uri;
            }

            public function getConfirmationLink($userId, $email)
            {
                return $this->routeParser->fullUrlFor(
                    $this->uri,
                    'confirm',
                    [],
                    ['id' => $userId, 'email' => $email]
                );
            }

            public function send($emailParams)
            {   
                $emailParams = array_merge($emailParams, [
                    'from' => 'Tar Heel Tailgate <noreply@' . $this->domain . '>',
                ]);

                try {
                    $this->mailgun->messages()->send($this->domain, $emailParams);
                    return true;
                } catch (\Throwable $e) {
                    // TODO: log failed email
                }
                return false;
            }
        };
    });
};