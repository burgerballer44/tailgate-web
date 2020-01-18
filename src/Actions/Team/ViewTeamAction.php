<?php

namespace TailgateWeb\Actions\Team;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view a team, its follows, and games
class ViewTeamAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        $apiResponse = $this->apiClient->get("/v1/teams/{$teamId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/team/view.twig', ['errors' => $data['errors']]);
        }

        $team = $data['data'];
        $eventLog = $team['eventLog'];
        return $this->view->render($this->response, 'admin/team/view.twig', compact('team', 'eventLog'));
    }
}