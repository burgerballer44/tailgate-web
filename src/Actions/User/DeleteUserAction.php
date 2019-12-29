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

        $clientResponse = $this->apiClient->delete("/v1/admin/users/{$userId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'admin/user/view.twig', ['errors' => $data['errors']]);
        }

        return $this->response->withHeader('Location', "/admin/users/{$userId}")->withStatus(302);
    }
}