<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// add game form
class AddGameAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/season/add-game.twig', compact('seasonId'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/v1/admin/seasons/{$seasonId}/game", [
            'seasonId' => $seasonId,
            'homeTeamId' => $parsedBody['home_team_id'],
            'awayTeamId' => $parsedBody['away_team_id'],
            'startDate' => $parsedBody['start_date'],
            'startTime' => $parsedBody['start_time']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'admin/season/add-game.twig', ['errors' => $data['errors'], 'seasonId' => $seasonId]);
        }

        return $this->response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }
}