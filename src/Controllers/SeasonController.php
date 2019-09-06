<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SeasonController extends AbstractController
{
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet('/v1/seasons');

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            var_dump($data);die();
        }

        $data = json_decode($clientResponse->getBody(), true);
        $seasons = $data['data'];

        return $this->view->render($response, 'season/index.twig', compact(('seasons')));
    }

    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['id'];

        $clientResponse = $this->apiGet('/v1/seasons/' . $seasonId);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            var_dump($data);die();
        }

        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];

        return $this->view->render($response, 'season/view.twig', compact('season'));
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'season/create.twig');
    }

    public function createPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/v1/seasons', [
            'sport' => $parsedBody['sport'],
            'season_type' => $parsedBody['season_type'],
            'name' => $parsedBody['name'],
            'season_start' => $parsedBody['season_start'],
            'season_end' => $parsedBody['season_end']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'season/create.twig', [
                'errors' => $data['errors'],
            ]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function addGame(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'season/add-game.twig');
    }

    public function addGamePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/v1/seasons/game', [
            'season_id' => $parsedBody['season_id'],
            'home_team_id' => $parsedBody['home_team_id'],
            'away_team_id' => $parsedBody['away_team_id'],
            'start_date' => $parsedBody['start_date']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'season/add-game.twig', [
                'errors' => $data['errors'],
            ]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function addGameScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'season/add-game-score.twig');
    }

    public function addGameScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/v1/seasons/game-score', [
            'season_id' => $parsedBody['season_id'],
            'game_id' => $parsedBody['game_id'],
            'home_team_score' => $parsedBody['home_team_score'],
            'away_team_score' => $parsedBody['away_team_score']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'season/add-game-score.twig', [
                'errors' => $data['errors'],
            ]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}