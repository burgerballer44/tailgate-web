<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// delete group for admin
class AdminDeleteGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {      
        extract($this->args);

        $clientResponse = $this->apiClient->delete("/v1/admin/groups/{$groupId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
        }

        return $this->response->withHeader('Location', "/admin/groups")->withStatus(302);
    }
}