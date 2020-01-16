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

        // get all teams
        $clientResponse = $this->apiClient->get("/v1/teams");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {            
            return $this->view->render($this->response, 'admin/team/index.twig', ['errors' => $data['errors']]);
        }

        $teams = collect($data['data'])->flatMap(function($team){
            return [$team['teamId'] => "{$team['designation']} {$team['mascot']}"];
        })->toArray();

        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/season/add-game.twig', compact('seasonId', 'teams'));
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
            return $this->view->render($this->response, 'admin/season/add-game.twig', [
                'errors' => $data['errors'],
                'teams' => $teams,
                'seasonId' => $seasonId,
            ]);
        }

        return $this->response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }
}