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

            public function getResetPasswordLink($token)
            {
                return $this->routeParser->fullUrlFor(
                    $this->uri,
                    'reset-password',
                    ['token' => $token]
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

    // scoring
    $container->set('scoring', function ($container) {
        return new class()
        {   
            private $playerNames;
            private $formattedData;

            public function generate($group, $season, $rules)
            {   
                // initialize as collections
                $players = collect($group['players'])->sortBy('username');
                $scores  = collect($group['scores']);
                $rules   = collect($rules);
                $games   = collect($season['games'])->sortBy('startDate');

                // get all player names for use in header
                $this->playerNames = $players->pluck('username');

                // gather all data and group it by games
                $this->formattedData = $games->reduce(function($carry, $game) use ($players, $scores, $rules) {

                    $homePredictions = $players->reduce(function($temp, $player) use ($game, $scores) {
                        $homePrediction  = $scores->where('playerId', $player['playerId'])->where('gameId', $game['gameId'])->first()['homeTeamPrediction'];
                        $temp[$player['playerId']] = $homePrediction;
                        return $temp;
                    }, collect([]));

                    $awayPredictions = $players->reduce(function($temp, $player) use ($game, $scores) {
                        $awayPrediction  = $scores->where('playerId', $player['playerId'])->where('gameId', $game['gameId'])->first()['awayTeamPrediction'];
                        $temp[$player['playerId']] = $awayPrediction;
                        return $temp;
                    }, collect([]));

                    $pointDifferences = $homePredictions->map(function($homePrediction, $playerId) use ($game, $awayPredictions) {
                        if (null == $homePrediction || null == $awayPredictions[$playerId] || null == $game['homeTeamScore'] || null == $game['awayTeamScore']) {
                            return null;
                        }
                        return abs(($game['homeTeamScore'] + $game['awayTeamScore']) - ($homePrediction + $awayPredictions[$playerId]));
                    });

                    $highestPointDifference = $pointDifferences->max();

                    $penaltyPoints = $homePredictions->map(function($homePrediction, $playerId) use ($awayPredictions, $highestPointDifference, $rules) {
                        if (null == $homePrediction || null == $awayPredictions[$playerId] ) {
                            return $highestPointDifference + 7;
                        }
                        return 0;
                    });

                    $finalPoints = $penaltyPoints->map(function($penaltyPoint, $playerId) use ($pointDifferences) {
                        return $penaltyPoint + $pointDifferences[$playerId];
                    });

                    // dd([$game['homeTeamScore'], $game['awayTeamScore']], $homePredictions, $awayPredictions, $pointDifferences, $penaltyPoints, $finalPoints);

                    $carry[$game['gameId']] = [
                        'homeTeam'         => $game['homeDesignation'] . ' ' . $game['homeMascot'],
                        'homeTeamScore'    => $game['homeTeamScore'],
                        'awayTeam'         => $game['awayDesignation'] . ' ' . $game['awayMascot'],
                        'awayTeamScore'    => $game['awayTeamScore'],
                        'homePredictions'  => $homePredictions,
                        'awayPredictions'  => $awayPredictions,
                        'pointDifferences' => $pointDifferences,
                        'penaltyPoints'    => $penaltyPoints,
                        'finalPoints'      => $finalPoints,
                    ];

                    return $carry;

                }, collect([]));

                // dd($this->formattedData);

                return $this;
            }

            public function getHtml()
            {   
                // table and header start
                $gridHtml = "<table cellpadding='5'><tr class='border-t-2 border-black'><th>Game</th><th>Final Score</th>";

                // add player names to header
                $gridHtml .= $this->playerNames->reduce(function($headerHtml, $player) {
                    $headerHtml .= "<th>{$player}</th>";
                    return $headerHtml;
                }, '');

                // end header
                $gridHtml .= '</tr>';

                // table data
                $gridHtml .= $this->formattedData->reduce(function($tableHtml, $data){

                    // home
                    $tableHtml .= "<tr class='border border-black border-t-2'>";
                    $tableHtml .= "<td class='border'>{$data['homeTeam']}</td>";
                    $tableHtml .= "<td class='border' align='center'>{$data['homeTeamScore']}</td>";
                    $tableHtml .= $data['homePredictions']->reduce(function($html, $score) {
                        $html .= "<td class='border'>{$score}</td>";
                        return $html;
                    }, '');
                    $tableHtml .= "</tr>";

                    // away
                    $tableHtml .= "<tr class='border'>";
                    $tableHtml .= "<td class='border'>{$data['awayTeam']}</td>";
                    $tableHtml .= "<td class='border' align='center'>{$data['awayTeamScore']}</td>";
                    $tableHtml .= $data['awayPredictions']->reduce(function($html, $score) {
                        $html .= "<td class='border'>{$score}</td>";
                        return $html;
                    }, '');
                    $tableHtml .= "</tr>";

                    // point differences
                    $tableHtml .= "<tr class='border'><td colspan='2' align='right' class='border'>Point Difference</td>";
                    $tableHtml .= $data['pointDifferences']->reduce(function($html, $score) {
                        $html .= "<td class='border'>{$score}</td>";
                        return $html;
                    }, '');
                    $tableHtml .= "</tr>";

                    // penalty points
                    $tableHtml .= "<tr class='border'><td colspan='2' align='right' class='border'>Penalty Points</td>";
                    $tableHtml .= $data['penaltyPoints']->reduce(function($html, $score) {
                        $html .= "<td class='border'>{$score}</td>";
                        return $html;
                    }, '');
                    $tableHtml .= "</tr>";

                    // final points
                    $tableHtml .= "<tr class='border'><td colspan='2' align='right' class='border'>Final Points</td>";
                    $tableHtml .= $data['finalPoints']->reduce(function($html, $score) {
                        $html .= "<td class='border'>{$score}</td>";
                        return $html;
                    }, '');
                    $tableHtml .= "</tr>";

                    return $tableHtml;
                }, '');

                return $gridHtml;
            }
        };
    });
};