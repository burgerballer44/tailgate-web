<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// add player form for admin
class AdminAddPlayerAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {   
        extract($this->args);

        // get the group
        $apiResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/admin/groups")->withStatus(302);
        }

        $group = $data['data'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/group/add-player.twig', compact('groupId', 'memberId', 'group'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/v1/admin/groups/{$groupId}/member/{$memberId}/player", [
            'username' => $parsedBody['username'],
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/group/add-player.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
                'group' => $group,
            ]);
        }

        return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}