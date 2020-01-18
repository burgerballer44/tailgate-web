<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// delete a user
class DeleteUserAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        extract($this->args);

        $apiResponse = $this->apiClient->delete("/v1/admin/users/{$userId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/user/view.twig', ['errors' => $data['errors']]);
        }

        return $this->response->withHeader('Location', "/admin/users/{$userId}")->withStatus(302);
    }
}