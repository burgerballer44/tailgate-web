<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// delete a season
class DeleteSeasonAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        extract($this->args);

        $clientResponse = $this->apiClient->delete("/v1/admin/seasons/{$seasonId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
        }

        return $this->response->withHeader('Location', '/admin/season')->withStatus(302);
    }
}