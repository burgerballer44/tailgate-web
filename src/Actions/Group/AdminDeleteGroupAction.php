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

        $apiResponse = $this->apiClient->delete("/v1/admin/groups/{$groupId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
        }

        return $this->response->withHeader('Location', "/admin/groups")->withStatus(302);
    }
}