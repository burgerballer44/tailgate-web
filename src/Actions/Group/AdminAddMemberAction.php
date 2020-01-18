<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// add member form for admin
class AdminAddMemberAction extends AbstractAction
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
            return $this->view->render($this->response, 'admin/group/add-member.twig', compact('groupId', 'group'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/v1/admin/groups/{$groupId}/member", ['userId' => $parsedBody['user_id']]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/group/add-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
            ]);
        }

        return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}