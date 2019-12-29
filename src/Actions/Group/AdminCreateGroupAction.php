<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// create group form for admin
class AdminCreateGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/group/create.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/v1/admin/groups", [
            'name' => $parsedBody['name'],
            'userId' => $parsedBody['user_id']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'admin/group/create.twig', ['errors' => $data['errors']]);
        }

        return $this->response->withHeader('Location', '/admin/groups')->withStatus(302);
    }
}