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

        // get the group, and member
        $apiResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }
        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('memberId', $memberId);
        if (!$member) {
            $this->flash->addMessage('error', 'Member is not part of the group.');
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        // get players of member
        $players = collect($group['players'])->where('memberId', $member['memberId'])->flatMap(function($player) {
            return [$player['playerId'] => $player['username']];
        })->toArray();
        if (empty($players)) {
            $this->flash->addMessage('error', 'Selected member has no players.');
            return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
        }

        // get the games
        $followId = $group['follow']['followId'];
        $apiResponse = $this->apiClient->get("/v1/seasons/follow/{$followId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }
        $games = collect($data['data'])->flatMap(function($game) {
            return [$game['gameId'] => "{$game['homeDesignation']} {$game['homeMascot']} vs {$game['awayDesignation']} {$game['awayMascot']} {$game['startDate']} / {$game['startTime']}"];
        })->toArray();

        $teamsInGames = collect($data['data'])->flatMap(function($game) {
            return [$game['gameId'] => [
                'homeTeam' => "{$game['homeDesignation']} {$game['homeMascot']}",
                'awayTeam' => "{$game['awayDesignation']} {$game['awayMascot']}",
            ]];
        })->toArray();

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/submit-score.twig', compact(
                'groupId',
                'memberId',
                'member',
                'group',
                'players',
                'games',
                'teamsInGames',
            ));
        }

        $parsedBody = $this->request->getParsedBody();

        $playerId = $parsedBody['player_id'];

        $apiResponse = $this->apiClient->post("/v1/groups/{$groupId}/player/{$playerId}/score", [
            'gameId' => $parsedBody['game_id'],
            'homeTeamPrediction' => $parsedBody['home_team_prediction'],
            'awayTeamPrediction' => $parsedBody['away_team_prediction']
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'group/submit-score.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
                'member' => $member,
                'group' => $group,
                'players' => $players,
                'games' => $games,
                'teamsInGames' => $teamsInGames
            ]);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}