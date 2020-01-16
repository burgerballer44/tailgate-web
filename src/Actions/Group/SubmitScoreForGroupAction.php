<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// submit score form
class SubmitScoreForGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {   
        extract($this->args);

        // get the group
        $clientResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        $group = $data['data'];
        $player = collect($group['players'])->firstWhere('playerId', $playerId);

        // get the games
        $followId = $group['follow']['followId'];
        $clientResponse = $this->apiClient->get("/v1/seasons/follow/{$followId}");
        $data = json_decode($clientResponse->getBody(), true);
        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }
        $games = collect($data['data'])->flatMap(function($game) {
            return [$game['gameId'] => "{$game['homeDesignation']} {$game['homeMascot']} vs {$game['awayDesignation']} {$game['awayMascot']} {$game['startDate']} / {$game['startTime']}"];
        })->toArray();


        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/submit-score.twig', compact('groupId', 'playerId', 'group', 'player', 'games'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/v1/groups/{$groupId}/player/{$playerId}/score", [
            'gameId' => $parsedBody['game_id'],
            'homeTeamPrediction' => $parsedBody['home_team_prediction'],
            'awayTeamPrediction' => $parsedBody['away_team_prediction']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($this->response, 'group/submit-score.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'playerId' => $playerId,
                'group' => $group,
                'player' => $player,
                'games' => $games
            ]);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}