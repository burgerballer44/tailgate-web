<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update score form for admin
class AdminUpdateScoreForGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {   
        extract($this->args);

        // get the group
        $apiResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        $group = $data['data'];
        $score = collect($group['scores'])->firstWhere('scoreId', $scoreId);
        $player = collect($group['players'])->firstWhere('playerId', $score['playerId']);

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/group/update-score.twig', compact('groupId', 'scoreId', 'score', 'player', 'group'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = $apiResponse->getData();
        $group = $data['data'];
        $score = collect($group['scores'])->firstWhere('scoreId', $scoreId);
    
        $apiResponse = $this->apiClient->patch("/v1/admin/groups/{$groupId}/score/{$scoreId}", [
            'homeTeamPrediction' => $parsedBody['home_team_prediction'],
            'awayTeamPrediction' => $parsedBody['away_team_prediction']
        ]);
    
        if ($apiResponse->hasErrors()) {
            $data = $apiResponse->getData();
    
            return $this->view->render($this->response, 'admin/group/update-score.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'scoreId' => $scoreId,
                'score' => $score,
                'player' => $player,
                'group' => $group
            ]);
        }
    
        return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}