<?php

use DI\ContainerBuilder;
use GuzzleHttp\Client;
use Knlv\Slim\Views\TwigMessages;
use Mailgun\Mailgun;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use TailgateWeb\Client\GuzzleTailgateApiClient;
use TailgateWeb\Client\TailgateApiClientInterface;
use TailgateWeb\Extensions\CsrfExtension;
use TailgateWeb\Extensions\EnvironmentExtension;
use TailgateWeb\Extensions\HelperExtension;
use TailgateWeb\Extensions\HoneypotExtension;
use TailgateWeb\Mailer\MailerInterface;
use TailgateWeb\Mailer\MailgunMailer;
use TailgateWeb\Middleware\AdminMiddleware;
use TailgateWeb\Middleware\CsrfMiddleware;
use TailgateWeb\Middleware\MustBeSignedInMiddleware;
use TailgateWeb\Middleware\MustBeSignedOutMiddleware;
use TailgateWeb\Middleware\UpdateUserSessionMiddleware;
use TailgateWeb\Scoring\DefaultScoring;
use TailgateWeb\Scoring\ScoringInterface;
use TailgateWeb\Session\SessionHelper;
use TailgateWeb\Session\SessionHelperInterface;

return function (ContainerBuilder $containerBuilder) {

    $containerBuilder->addDefinitions([

        // slim app
        App::class => function (ContainerInterface $container) {
            AppFactory::setContainer($container);
            $app = AppFactory::create();
            return $app;
        },

        // response factory
        ResponseFactoryInterface::class => function (ContainerInterface $container) {
            return $container->get(App::class)->getResponseFactory();
        },

        // route parser
        RouteParserInterface::class => function (ContainerInterface $container) {
            return $container->get(App::class)->getRouteCollector()->getRouteParser();
        },

        // pdo connection
        PDO::class => function (ContainerInterface $container) {
            $settings   = $container->get('settings')['pdo'];
            $connection = $settings['connection'];
            $host       = $settings['host'];
            $port       = $settings['port'];
            $database   = $settings['database'];
            $username   = $settings['username'];
            $password   = $settings['password'];

            return new PDO("{$connection}:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        },

        // wrapper for handling $_SESSION
        SessionHelperInterface::class => function () {
            return new SessionHelper;
        },

        // csrf
        'csrf' => function (ContainerInterface $container) {
            return new CsrfMiddleware('csrf', $container->get(ResponseFactoryInterface::class));
        },

        // flash
        Messages::class => function () {
            return new Messages();
        },

        Twig::class => function (ContainerInterface $container) {
            $twig = new Twig(__DIR__ . '/Views/', [
                'cache' => PROD_MODE ?  __DIR__ . '/../var/cache/twig/' : false,
                'auto_reload' => true,
                'debug' => $container->get('settings')['errorHandlerMiddleware']['displayErrorDetails'],
                'strict_variables' => $container->get('settings')['errorHandlerMiddleware']['displayErrorDetails'],
            ]);

            $twig->addExtension(new HoneypotExtension());
            $twig->addExtension(new CsrfExtension($container->get('csrf')));
            $twig->addExtension(new TwigMessages($container->get(Messages::class)));
            $twig->addExtension(new HelperExtension($container->get(SessionHelperInterface::class)));
            $twig->addExtension(new EnvironmentExtension(PROD_MODE));

            return $twig;
        },

        // view
        'view' => function (ContainerInterface $container) {
            return $container->get(Twig::class);
        },

        // other middleware that are set to individual routes
        AdminMiddleware::class => function (ContainerInterface $container) {
            return new AdminMiddleware(
                $container->get(SessionHelperInterface::class),
                $container->get(Messages::class),
                $container->get(ResponseFactoryInterface::class)
            );
        },
        MustBeSignedInMiddleware::class => function (ContainerInterface $container) {
            return new MustBeSignedInMiddleware(
                $container->get(SessionHelperInterface::class),
                $container->get(ResponseFactoryInterface::class)
            );
        },
        MustBeSignedOutMiddleware::class => function (ContainerInterface $container) {
            return new MustBeSignedOutMiddleware(
                $container->get(SessionHelperInterface::class),
                $container->get(ResponseFactoryInterface::class)
            );
        },
        UpdateUserSessionMiddleware::class => function (ContainerInterface $container) {
            return new UpdateUserSessionMiddleware(
                $container->get(SessionHelperInterface::class),
                $container->get(TailgateApiClientInterface::class)
            );
        },


        // logger
        LoggerInterface::class => function (ContainerInterface $container) {
            $settings = $container->get('settings');
            $loggerSettings = $settings['logger'];
            $logger = new Logger($loggerSettings['name']);
            $processor = new UidProcessor();
            $logger->pushProcessor($processor);
            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);
            return $logger;
        },

        // client to access API
        Client::class => function (ContainerInterface $container) {
            $client = new GuzzleHttp\Client([
                'base_uri' => $container->get('settings')['api'],
                'allow_redirects' => false,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
            return $client;
        },

        TailgateApiClientInterface::class => function (ContainerInterface $container) {
            return new GuzzleTailgateApiClient(
                $container->get(Client::class),
                $container->get(SessionHelperInterface::class),
                $container->get(ResponseFactoryInterface::class),
                $container->get(Messages::class),
                $container->get(LoggerInterface::class),
                [
                    'clientId' => $container->get('settings')['client_id'],
                    'clientSecret' => $container->get('settings')['client_secret'],
                ]
            );
        },

        Mailgun::class => function (ContainerInterface $container) {
            return Mailgun::create($container->get('settings')['mailgun_api_key']);
        },

        // mailer
        MailerInterface::class => function (ContainerInterface $container) {
            return new MailgunMailer(
                $container->get(Mailgun::class),
                $container->get(RouteParserInterface::class),
                $container->get(LoggerInterface::class),
                $container->get('settings')['mailgun_domain'],
                $container->get('settings')['mailgun_test_mode']
            );
        },

        // scoring
        ScoringInterface::class => function () {
            return new DefaultScoring();
        },

    ]);
};