<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// all groups for admin
class AllGroupsAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {   
        $apiResponse = $this->apiClient->get("/v1/admin/groups");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/group/index.twig');
        }

        $groups = $data['data'];
        return $this->view->render($this->response, 'admin/group/index.twig', compact('groups'));
    }
}