<?php

namespace TailgateWeb\Actions\Team;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// admin follow form
class AdminFollowTeamAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/team/follow.twig', compact('teamId'));
        }

       $parsedBody = $this->request->getParsedBody();

        $groupId = $parsedBody['group_id'];
        $seasonId = $parsedBody['season_id'];

        $clientResponse = $this->apiClient->post("/v1/groups/{$groupId}/follow", [
            'teamId' => $teamId,
            'seasonId' => $seasonId
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'admin/team/follow.twig', ['errors' => $data['errors'],'teamId' => $teamId]);
        }

        return $this->response->withHeader('Location', "/admin/team/{$teamId}")->withStatus(302);
    }
}