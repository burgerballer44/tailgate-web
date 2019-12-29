<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// add player form
class AddPlayerAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {   
        extract($this->args);

        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/add-player.twig', compact('groupId', 'memberId'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/v1/groups/{$groupId}/member/{$memberId}/player", [
            'username' => $parsedBody['username'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($this->response, 'group/add-player.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
            ]);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}