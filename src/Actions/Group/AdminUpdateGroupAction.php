<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update group form for admin
class AdminUpdateGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {   
        extract($this->args);

        // get the group
        $apiResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/group/update.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
            ]);
        }
        $group = $data['data'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/group/update.twig', compact('group', 'groupId'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->patch("/v1/admin/groups/{$groupId}", [
            'name' => $parsedBody['name'],
            'ownerId' => $group['ownerId'],
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/group/update.twig', [
                'errors' => $data['errors'],
                'group' => $group,
                'groupId' => $groupId,
            ]);
        }

        return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}