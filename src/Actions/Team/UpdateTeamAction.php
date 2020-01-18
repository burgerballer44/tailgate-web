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

        // get team
        $apiResponse = $this->apiClient->get("/v1/teams/{$teamId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/team/update.twig', ['errors' => $data['errors']]);
        }
        $team = $data['data'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/team/update.twig', compact('team'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->patch("/v1/admin/teams/{$teamId}", [
            'designation' => $parsedBody['designation'],
            'mascot' => $parsedBody['mascot']
        ]);

        if ($apiResponse->hasErrors()) {
            $data = $apiResponse->getData();

            return $this->view->render($this->response, 'admin/team/update.twig', [
                'errors' => $data['errors'],
                'teamId' => $teamId,
                'team' => $team
            ]);
        }

        return $this->response->withHeader('Location', "/admin/team/{$teamId}")->withStatus(302);
    }
}