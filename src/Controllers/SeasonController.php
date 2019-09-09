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
            
            return $this->view->render($response, 'season/index.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $data = json_decode($clientResponse->getBody(), true);
        $seasons = $data['data'];

        return $this->view->render($response, 'season/index.twig', compact(('seasons')));
    }

    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'season/view.twig', [
                'errors' => $data['errors'],
            ]);
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

        return $response->withHeader('Location', '/season')->withStatus(302);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'season/index.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];

        return $this->view->render($response, 'season/update.twig', compact('season', 'seasonId'));
    }

    public function updatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPatch("/v1/seasons/{$seasonId}", [
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

        return $response->withHeader('Location', '/season')->withStatus(302);
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];

        $clientResponse = $this->apiDelete("/v1/seasons/{$seasonId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'season/create.twig', [
                'errors' => $data['errors'],
            ]);
        }

        return $response->withHeader('Location', '/season')->withStatus(302);
    }

    public function addGame(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $seasonId = $args['seasonId'];
        return $this->view->render($response, 'season/add-game.twig', compact('seasonId'));
    }

    public function addGamePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $seasonId = $args['seasonId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/seasons/{$seasonId}/game", [
            'season_id' => $seasonId,
            'home_team_id' => $parsedBody['home_team_id'],
            'away_team_id' => $parsedBody['away_team_id'],
            'start_date' => $parsedBody['start_date']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            var_dump($data);
            die();

            return $this->view->render($response, 'season/add-game.twig', [
                'errors' => $data['errors'],
                'seasonId' => $seasonId,
            ]);
        }

        return $response->withHeader('Location', '/season')->withStatus(302);
    }

    public function updateGameScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];
        $gameId = $args['gameId'];
        return $this->view->render($response, 'season/update-game-score.twig', compact('seasonId', 'gameId'));
    }

    public function updateGameScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $seasonId = $args['seasonId'];
        $gameId = $args['gameId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/seasons/{$seasonId}/game/{$gameId}", [
            'season_id' => $seasonId,
            'game_id' => $gameId,
            'home_team_score' => $parsedBody['home_team_score'],
            'away_team_score' => $parsedBody['away_team_score']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'season/update-game-score.twig', [
                'errors' => $data['errors'],
                'seasonId' => $seasonId,
                'gameId' => $gameId,
            ]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function deleteGame(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];
        $gameId = $args['gameId'];

        $clientResponse = $this->apiDelete("/v1/seasons/{$seasonId}/game/{$gameId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'season/create.twig', [
                'errors' => $data['errors'],
            ]);
        }

        return $response->withHeader('Location', '/season')->withStatus(302);
    }
}