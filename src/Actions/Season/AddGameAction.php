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
        $apiResponse = $this->apiClient->get("/v1/teams");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {            
            return $this->view->render($this->response, 'admin/team/index.twig', ['errors' => $data['errors']]);
        }

        $teams = collect($data['data'])->flatMap(function($team){
            return [$team['teamId'] => "{$team['designation']} {$team['mascot']}"];
        })->toArray();

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/season/add-game.twig', compact('seasonId', 'teams'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/v1/admin/seasons/{$seasonId}/game", [
            'seasonId' => $seasonId,
            'homeTeamId' => $parsedBody['home_team_id'],
            'awayTeamId' => $parsedBody['away_team_id'],
            'startDate' => $parsedBody['start_date'],
            'startTime' => $parsedBody['start_time']
        ]);

        if ($apiResponse->hasErrors()) {
            $data = $apiResponse->getData();
            return $this->view->render($this->response, 'admin/season/add-game.twig', [
                'errors' => $data['errors'],
                'teams' => $teams,
                'seasonId' => $seasonId,
            ]);
        }

        return $this->response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }
}