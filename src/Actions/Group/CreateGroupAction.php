<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// create group form
class CreateGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/create.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/v1/groups", [
            'name' => $parsedBody['name'],
            'userId' => $this->session->get('user')['userId'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'group/create.twig', ['errors' => $data['errors']]);
        }

        return $this->response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}