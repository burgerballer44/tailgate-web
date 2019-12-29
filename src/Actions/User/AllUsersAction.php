<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view all users
class AllUsersAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $clientResponse = $this->apiClient->get("/v1/admin/users");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($this->response, 'admin/user/index.twig');
        }

        $users = $data['data'];
        return $this->view->render($this->response, 'admin/user/index.twig', compact('users'));
    }
}