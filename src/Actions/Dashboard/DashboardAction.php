<?php

namespace TailgateWeb\Actions\Dashboard;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view dashboard
class DashboardAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $apiResponse = $this->apiClient->get("/v1/groups");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'dashboard.twig');
        }

        $groups = $data['data'];
        return $this->view->render($this->response, 'dashboard.twig', compact('groups'));
    }
}