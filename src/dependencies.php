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
use TailgateWeb\Extensions\HelperExtension;
use TailgateWeb\Extensions\HoneypotExtension;
use TailgateWeb\Mailer\MailerInterface;
use TailgateWeb\Mailer\MailgunMailer;
use TailgateWeb\Middleware\AdminMiddleware;
use TailgateWeb\Middleware\CsrfMiddleware;
use TailgateWeb\Middleware\MustBeSignedInMiddleware;
use TailgateWeb\Middleware\MustBeSignedOutMiddleware;
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
            $twig = new Twig(__DIR__ . '/../views/', [
                'cache' => PROD_MODE ?  __DIR__ . '/../var/cache/twig/' : false,
                'auto_reload' => true,
                'debug' => $container->get('settings')['errorHandlerMiddleware']['displayErrorDetails'],
                'strict_variables' => $container->get('settings')['errorHandlerMiddleware']['displayErrorDetails'],
            ]);

            $twig->addExtension(new HoneypotExtension());
            $twig->addExtension(new CsrfExtension($container->get('csrf')));
            $twig->addExtension(new TwigMessages($container->get(Messages::class)));
            $twig->addExtension(new HelperExtension($container->get(SessionHelperInterface::class)));

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
                $container->get('settings')['mailgun_domain'],
                $container->get('settings')['mailgun_test_mode']
            );
        },

        // scoring
        'scoring' => function (ContainerInterface $container) {
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
        },

    ]);
};