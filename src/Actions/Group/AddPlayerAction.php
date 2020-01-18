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

        // get the group
        $apiResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }
        $group = $data['data'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/add-player.twig', compact('groupId', 'memberId', 'group'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/v1/groups/{$groupId}/member/{$memberId}/player", [
            'username' => $parsedBody['username'],
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {

            return $this->view->render($this->response, 'group/add-player.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
                'group' => $group,
            ]);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}