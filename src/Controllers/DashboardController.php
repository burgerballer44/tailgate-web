<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DashboardController extends AbstractController
{
    /**
     * [dashboard description]
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function dashboard(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/groups");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'dashboard.twig');
        }

        $groups = $data['data'];
        return $this->view->render($response, 'dashboard.twig', compact('groups'));
    }
}