<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view all users
class AllUsersAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $apiResponse = $this->apiClient->get("/v1/admin/users");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/user/index.twig');
        }

        $users = $data['data'];
        return $this->view->render($this->response, 'admin/user/index.twig', compact('users'));
    }
}