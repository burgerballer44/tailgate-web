<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DashboardController extends AbstractController
{
    public function dashboard(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'dashboard.twig');
    }
}