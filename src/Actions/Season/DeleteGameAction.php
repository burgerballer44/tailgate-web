<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// delete game form
class DeleteGameAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        $apiResponse = $this->apiClient->delete("/v1/admin/seasons/{$seasonId}/game/{$gameId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
        }

        return $this->response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }
}