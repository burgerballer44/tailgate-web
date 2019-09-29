<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SeasonController extends AbstractController
{   
    // view all saeasons
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/seasons");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            
            return $this->view->render($response, 'season/index.twig', ['errors' => $data['errors']]);
        }

        $seasons = $data['data'];
        return $this->view->render($response, 'season/index.twig', compact(('seasons')));
    }

    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'season/view.twig', ['errors' => $data['errors']]);
        }

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

        $clientResponse = $this->apiPost("/v1/seasons", [
            'name' => $parsedBody['name'],
            'sport' => $parsedBody['sport'],
            'seasonType' => $parsedBody['season_type'],
            'seasonStart' => $parsedBody['season_start'],
            'seasonEnd' => $parsedBody['season_end']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'season/create.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', '/season')->withStatus(302);
    }

    // update season form
    public function update(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'season/index.twig', ['errors' => $data['errors']]);
        }

        $season = $data['data'];
        $season['seasonStart'] = \DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $season['seasonStart'])->format("Y-m-d");
        $season['seasonEnd'] = \DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $season['seasonEnd'])->format("Y-m-d");
        return $this->view->render($response, 'season/update.twig', compact('season', 'seasonId'));
    }

    // submit update season form
    public function updatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];
        $season['seasonStart'] = \DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $season['seasonStart'])->format("Y-m-d");
        $season['seasonEnd'] = \DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $season['seasonEnd'])->format("Y-m-d");

        $clientResponse = $this->apiPatch("/v1/seasons/{$seasonId}", [
            'sport' => $parsedBody['sport'],
            'seasonType' => $parsedBody['season_type'],
            'name' => $parsedBody['name'],
            'seasonStart' => $parsedBody['season_start'],
            'seasonEnd' => $parsedBody['season_end']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'season/update.twig', [
                'errors' => $data['errors'],
                'season' => $season,
                'seasonId' => $seasonId,
            ]);
        }

        return $response->withHeader('Location', "/season/{$seasonId}")->withStatus(302);
    }

    // delete a season
    public function delete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];

        $clientResponse = $this->apiDelete("/v1/seasons/{$seasonId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'season/create.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', '/season')->withStatus(302);
    }

    // add game form
    public function addGame(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $seasonId = $args['seasonId'];
        return $this->view->render($response, 'season/add-game.twig', compact('seasonId'));
    }

    // submit add game form
    public function addGamePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $seasonId = $args['seasonId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/seasons/{$seasonId}/game", [
            'seasonId' => $seasonId,
            'homeTeamId' => $parsedBody['home_team_id'],
            'awayTeamId' => $parsedBody['away_team_id'],
            'startDate' => $parsedBody['start_date']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'season/add-game.twig', ['errors' => $data['errors'], 'seasonId' => $seasonId]);
        }

        return $response->withHeader('Location', "/season/{$seasonId}")->withStatus(302);
    }

    public function updateGameScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];
        $gameId = $args['gameId'];

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];
        $game = collect($season['games'])->firstWhere('gameId', $gameId);
        $game['startDate'] = \DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $game['startDate'])->format("Y-m-d H:i");
        return $this->view->render($response, 'season/update-game-score.twig', compact('seasonId', 'gameId', 'game'));
    }

    public function updateGameScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $seasonId = $args['seasonId'];
        $gameId = $args['gameId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];
        $game = collect($season['games'])->firstWhere('gameId', $gameId);
        $game['startDate'] = \DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $game['startDate'])->format("Y-m-d H:i");

        $clientResponse = $this->apiPatch("/v1/seasons/{$seasonId}/game/{$gameId}/score", [
            'seasonId' => $seasonId,
            'gameId' => $gameId,
            'homeTeamScore' => $parsedBody['home_team_score'],
            'awayTeamScore' => $parsedBody['away_team_score'],
            'startDate' => $parsedBody['start_date']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'season/update-game-score.twig', [
                'errors' => $data['errors'],
                'seasonId' => $seasonId,
                'gameId' => $gameId,
                'game' => $game,
            ]);
        }

        return $response->withHeader('Location', "/season/{$seasonId}")->withStatus(302);
    }

    public function deleteGame(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $seasonId = $args['seasonId'];
        $gameId = $args['gameId'];

        $clientResponse = $this->apiDelete("/v1/seasons/{$seasonId}/game/{$gameId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'season/index.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', "/season/{$seasonId}")->withStatus(302);
    }
}