<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view a single user
class ViewUserAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        extract($this->args);

        $apiResponse = $this->apiClient->get("/v1/admin/users/{$userId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/user/view.twig');
        }

        $user = $data['data'];
        $eventLog = $user['eventLog'];
        return $this->view->render($this->response, 'admin/user/view.twig', compact('user', 'eventLog'));
    }
}