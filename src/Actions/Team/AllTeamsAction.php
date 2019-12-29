<?php

namespace TailgateWeb\Actions\Team;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view all team
class AllTeamsAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $clientResponse = $this->apiClient->get("/v1/teams");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {            
            return $this->view->render($this->response, 'admin/team/index.twig', ['errors' => $data['errors']]);
        }

        $teams = $data['data'];
        return $this->view->render($this->response, 'admin/team/index.twig', compact('teams'));
    }
}