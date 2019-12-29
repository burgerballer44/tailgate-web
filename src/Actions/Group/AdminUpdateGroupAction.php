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

        if ('POST' != $this->request->getMethod()) {
            $clientResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
            $data = json_decode($clientResponse->getBody(), true);

            if ($clientResponse->getStatusCode() >= 400) {
                return $this->view->render($this->response, 'admin/group/update.twig', [
                    'errors' => $data['errors'],
                    'groupId' => $groupId,
                ]);
            }

            $group = $data['data'];
            return $this->view->render($this->response, 'admin/group/update.twig', compact('group', 'groupId'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];

        $clientResponse = $this->apiClient->patch("/v1/admin/groups/{$groupId}", [
            'name' => $parsedBody['name'],
            'ownerId' => $group['ownerId'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($this->response, 'admin/group/update.twig', [
                'errors' => $data['errors'],
                'group' => $group,
                'groupId' => $groupId,
            ]);
        }

        return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}