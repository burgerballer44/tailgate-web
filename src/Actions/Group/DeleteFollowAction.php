<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// delete follow
class DeleteFollowAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {      
        extract($this->args);

        $apiResponse = $this->apiClient->delete("/v1/groups/{$groupId}/follow/{$followId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}