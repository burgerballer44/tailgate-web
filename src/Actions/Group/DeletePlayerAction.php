<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// delete player form
class DeletePlayerAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {      
        extract($this->args);

        $clientResponse = $this->apiClient->delete("/v1/groups/{$groupId}/player/{$playerId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}