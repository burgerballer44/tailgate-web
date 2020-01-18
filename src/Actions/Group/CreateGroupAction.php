<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// create group form
class CreateGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/create.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/v1/groups", [
            'name' => $parsedBody['name'],
            'userId' => $this->session->get('user')['userId'],
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'group/create.twig', ['errors' => $data['errors']]);
        }

        return $this->response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}