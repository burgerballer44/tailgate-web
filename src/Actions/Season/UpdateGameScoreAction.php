<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update game score form
class UpdateGameScoreAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        if ('POST' != $this->request->getMethod()) {
            $clientResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
            $data = json_decode($clientResponse->getBody(), true);
            $season = $data['data'];
            $game = collect($season['games'])->firstWhere('gameId', $gameId);
            return $this->view->render($this->response, 'admin/season/update-game-score.twig', compact('seasonId', 'gameId', 'game'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];
        $game = collect($season['games'])->firstWhere('gameId', $gameId);

        $clientResponse = $this->apiClient->patch("/v1/admin/seasons/{$seasonId}/game/{$gameId}/score", [
            'seasonId' => $seasonId,
            'gameId' => $gameId,
            'homeTeamScore' => $parsedBody['home_team_score'],
            'awayTeamScore' => $parsedBody['away_team_score'],
            'startDate' => $parsedBody['start_date'],
            'startTime' => $parsedBody['start_time']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($this->response, 'admin/season/update-game-score.twig', [
                'errors' => $data['errors'],
                'seasonId' => $seasonId,
                'gameId' => $gameId,
                'game' => $game,
            ]);
        }

        return $this->response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }
}