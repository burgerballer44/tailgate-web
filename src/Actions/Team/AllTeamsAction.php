<?php

namespace TailgateWeb\Actions\Team;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view all team
class AllTeamsAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $apiResponse = $this->apiClient->get("/v1/teams");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {            
            return $this->view->render($this->response, 'admin/team/index.twig', ['errors' => $data['errors']]);
        }

        $teams = $data['data'];
        return $this->view->render($this->response, 'admin/team/index.twig', compact('teams'));
    }
}