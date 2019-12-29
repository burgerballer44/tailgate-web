<?php

namespace TailgateWeb\Actions\Team;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// admin delete follow form
class AdminDeleteFollowAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->delete("/v1/groups/{$groupId}/follow/{$followId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
            return $this->view->render($this->response, 'admin/team/view.twig', ['errors' => $data['errors']]);
        }

        return $this->response->withHeader('Location', "/admin/team/{$teamId}")->withStatus(302);
    }
}