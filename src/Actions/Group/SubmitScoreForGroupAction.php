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

        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/submit-score.twig', compact('groupId', 'playerId'));
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
            ]);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}