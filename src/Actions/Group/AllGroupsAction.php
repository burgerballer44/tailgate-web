<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// all groups for admin
class AllGroupsAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {   
        $clientResponse = $this->apiClient->get("/v1/admin/groups");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($this->response, 'admin/group/index.twig');
        }

        $groups = $data['data'];
        return $this->view->render($this->response, 'admin/group/index.twig', compact('groups'));
    }
}