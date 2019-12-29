<?php

namespace TailgateWeb\Actions\Dashboard;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view dashboard
class DashboardAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $clientResponse = $this->apiClient->get("/v1/groups");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($this->response, 'dashboard.twig');
        }

        $groups = $data['data'];
        return $this->view->render($this->response, 'dashboard.twig', compact('groups'));
    }
}