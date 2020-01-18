<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// get the list of teams in a season
class TeamListAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        $apiResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            $this->response->getBody()->write(json_encode('nope'));
            return $this->response->withHeader('Content-Type', 'application/json')->withStatus(500);
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
        $this->response->getBody()->write($payload);
        return $this->response->withHeader('Content-Type', 'application/json');
    }
}