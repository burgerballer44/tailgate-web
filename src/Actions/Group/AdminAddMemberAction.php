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

        if ('POST' != $this->request->getMethod()) {
            $clientResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
            $data = json_decode($clientResponse->getBody(), true);

            if ($clientResponse->getStatusCode() >= 400) {
                $this->flash->addMessage('error', $data['errors']);
                return $this->response->withHeader('Location', "/admin/groups")->withStatus(302);
            }

            $group = $data['data'];
            return $this->view->render($this->response, 'admin/group/add-member.twig', compact('groupId', 'group'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/v1/admin/groups/{$groupId}/member", ['userId' => $parsedBody['user_id']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($this->response, 'admin/group/add-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
            ]);
        }

        return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}