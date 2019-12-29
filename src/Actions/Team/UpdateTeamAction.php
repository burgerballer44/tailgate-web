<?php

namespace TailgateWeb\Actions\Team;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update team form
class UpdateTeamAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        if ('POST' != $this->request->getMethod()) {

            $clientResponse = $this->apiClient->get("/v1/teams/{$teamId}");
            $data = json_decode($clientResponse->getBody(), true);

            if ($clientResponse->getStatusCode() >= 400) {
                return $this->view->render($this->response, 'admin/team/update.twig', ['errors' => $data['errors']]);
            }

            $team = $data['data'];
            return $this->view->render($this->response, 'admin/team/update.twig', compact('team'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->get("/v1/teams/{$teamId}");
        $data = json_decode($clientResponse->getBody(), true);
        $team = $data['data'];

        $clientResponse = $this->apiClient->patch("/v1/admin/teams/{$teamId}", [
            'designation' => $parsedBody['designation'],
            'mascot' => $parsedBody['mascot']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($this->response, 'admin/team/update.twig', [
                'errors' => $data['errors'],
                'teamId' => $teamId,
                'team' => $team
            ]);
        }

        return $this->response->withHeader('Location', "/admin/team/{$teamId}")->withStatus(302);
    }
}