<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// create group form for admin
class AdminCreateGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/group/create.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/v1/admin/groups", [
            'name' => $parsedBody['name'],
            'userId' => $parsedBody['user_id']
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/group/create.twig', ['errors' => $data['errors']]);
        }

        return $this->response->withHeader('Location', '/admin/groups')->withStatus(302);
    }
}