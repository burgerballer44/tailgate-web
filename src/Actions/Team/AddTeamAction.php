<?php

namespace TailgateWeb\Actions\Team;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// add team form
class AddTeamAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {   
        $sports = ['Football' => 'Football', 'Basketball' => 'Basketball'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/team/add.twig', compact('sports'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/v1/admin/teams", [
            'designation' => $parsedBody['designation'],
            'mascot' => $parsedBody['mascot'],
            'sport' => $parsedBody['sport']
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/team/add.twig', [
                'errors' => $data['errors'],
                'sports' => $sports,
            ]);
        }

        return $this->response->withHeader('Location', '/admin/team')->withStatus(302);
    }
}