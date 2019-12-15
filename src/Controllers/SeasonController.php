<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SeasonController extends AbstractController
{   
    /**
     * view all seasons
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/seasons");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/season/index.twig', ['errors' => $data['errors']]);
        }

        $seasons = $data['data'];
        return $this->view->render($response, 'admin/season/index.twig', compact('seasons'));
    }

    /**
     * view a season
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/season/view.twig', ['errors' => $data['errors']]);
        }

        $season = $data['data'];
        $games = $season['games'];
        return $this->view->render($response, 'admin/season/view.twig', compact('season', 'games'));
    }

    /**
     * create season form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $sports = ['Football' => 'Football', 'Basketball' => 'Basketball'];
        $seasonTypes = ['Regular-Season' => 'Regular-Season'];
        return $this->view->render($response, 'admin/season/create.twig', compact('sports', 'seasonTypes'));
    }

    /**
     * submit create season form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function createPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/admin/seasons", [
            'name' => $parsedBody['name'],
            'sport' => $parsedBody['sport'],
            'seasonType' => $parsedBody['season_type'],
            'seasonStart' => $parsedBody['season_start'],
            'seasonEnd' => $parsedBody['season_end']
        ]);

        $sports = ['Football' => 'Football', 'Basketball' => 'Basketball'];
        $seasonTypes = ['Regular-Season' => 'Regular-Season'];

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'admin/season/create.twig', [
                'errors' => $data['errors'],
                'sports' => $sports, 
                'seasonTypes' => $seasonTypes
            ]);
        }

        return $response->withHeader('Location', '/admin/season')->withStatus(302);
    }

    /**
     * update season form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/season/update.twig', ['errors' => $data['errors']]);
        }

        $season = $data['data'];
        $sports = ['Football' => 'Football', 'Basketball' => 'Basketball'];
        $seasonTypes = ['Regular-Season' => 'Regular-Season'];

        return $this->view->render($response, 'admin/season/update.twig', compact('season', 'seasonId', 'sports', 'seasonTypes'));
    }

    /**
     * submit update season form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function updatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];

        $sports = ['Football' => 'Football', 'Basketball' => 'Basketball'];
        $seasonTypes = ['Regular-Season' => 'Regular-Season'];

        $clientResponse = $this->apiPatch("/v1/admin/seasons/{$seasonId}", [
            'sport' => $parsedBody['sport'],
            'seasonType' => $parsedBody['season_type'],
            'name' => $parsedBody['name'],
            'seasonStart' => $parsedBody['season_start'],
            'seasonEnd' => $parsedBody['season_end']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'admin/season/update.twig', [
                'errors' => $data['errors'],
                'season' => $season,
                'seasonId' => $seasonId,
                'sports' => $sports,
                'seasonTypes' => $seasonTypes
            ]);
        }

        return $response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }

    /**
     * delete a season
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiDelete("/v1/admin/seasons/{$seasonId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
            return $response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
        }

        return $response->withHeader('Location', '/admin/season')->withStatus(302);
    }

    /**
     * add game form
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function addGame(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        return $this->view->render($response, 'admin/season/add-game.twig', compact('seasonId'));
    }

    /**
     * submit add game form
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function addGamePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/admin/seasons/{$seasonId}/game", [
            'seasonId' => $seasonId,
            'homeTeamId' => $parsedBody['home_team_id'],
            'awayTeamId' => $parsedBody['away_team_id'],
            'startDate' => $parsedBody['start_date'],
            'startTime' => $parsedBody['start_time']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'admin/season/add-game.twig', ['errors' => $data['errors'], 'seasonId' => $seasonId]);
        }

        return $response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }

    /**
     * update game score form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function updateGameScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];
        $game = collect($season['games'])->firstWhere('gameId', $gameId);
        return $this->view->render($response, 'admin/season/update-game-score.twig', compact('seasonId', 'gameId', 'game'));
    }

    /**
     * submit update game score form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function updateGameScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];
        $game = collect($season['games'])->firstWhere('gameId', $gameId);

        $clientResponse = $this->apiPatch("/v1/admin/seasons/{$seasonId}/game/{$gameId}/score", [
            'seasonId' => $seasonId,
            'gameId' => $gameId,
            'homeTeamScore' => $parsedBody['home_team_score'],
            'awayTeamScore' => $parsedBody['away_team_score'],
            'startDate' => $parsedBody['start_date'],
            'startTime' => $parsedBody['start_time']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'admin/season/update-game-score.twig', [
                'errors' => $data['errors'],
                'seasonId' => $seasonId,
                'gameId' => $gameId,
                'game' => $game,
            ]);
        }

        return $response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }

    /**
     * delete a game from a season
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function deleteGame(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiDelete("/v1/admin/seasons/{$seasonId}/game/{$gameId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }

    /**
     * get the list of teams in a season
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function teamlist(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $response->getBody()->write(json_encode('nope'));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $gamesInSeason = collect($data['data']['games']);

        $homeTeams = $gamesInSeason->groupBy('homeTeamId')->map(function($games) {
            return $games->first();
        })->map(function($game) {
            return ['teamId' => $game['homeTeamId'], 'teamName' => $game['homeDesignation'] . ' ' . $game['homeMascot']];
        });
        $awayTeams = $gamesInSeason->groupBy('awayTeamId')->map(function($games) {
            return $games->first();
        })->map(function($game) {
            return ['teamId' => $game['awayTeamId'], 'teamName' => $game['awayDesignation'] . ' ' . $game['awayMascot']];
        });
        $teams = $homeTeams->merge($awayTeams)->sortBy('teamName')->values();

        $payload = json_encode($teams);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}