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

        if ('POST' != $this->request->getMethod()) {
            $clientResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
            $data = json_decode($clientResponse->getBody(), true);
            $group = $data['data'];
            $score = collect($group['scores'])->firstWhere('scoreId', $scoreId);

            return $this->view->render($this->response, 'admin/group/update-score.twig', compact('groupId', 'scoreId', 'score'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $score = collect($group['scores'])->firstWhere('scoreId', $scoreId);
    
        $clientResponse = $this->apiClient->patch("/v1/admin/groups/{$groupId}/score/{$scoreId}", [
            'homeTeamPrediction' => $parsedBody['home_team_prediction'],
            'awayTeamPrediction' => $parsedBody['away_team_prediction']
        ]);
    
        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
    
            return $this->view->render($this->response, 'admin/group/update-score.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'scoreId' => $scoreId,
                'score' => $score,
            ]);
        }
    
        return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}