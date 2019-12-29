<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view group for admin
class AdminViewGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        $clientResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/admin/groups")->withStatus(302);
        }

        $group = $data['data'];
        $eventLog = $group['eventLog'];
        return $this->view->render($this->response, 'admin/group/view.twig', compact('group', 'groupId', 'eventLog'));
    }
}