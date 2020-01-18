<?php

namespace TailgateWeb\Actions\Team;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// delete team form
class DeleteTeamAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        $apiResponse = $this->apiClient->delete("/v1/admin/teams/{$teamId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
        }

        return $this->response->withHeader('Location', '/admin/team')->withStatus(302);
    }
}