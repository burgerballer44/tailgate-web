<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

// add session stuff to view
class AddGlobalsToTwigMiddleware
{
    protected $session;
    protected $view;

    public function __construct($session, $view)
    {
        $this->session = $session;
        $this->view = $view;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {   
        if (isset($this->session->user)) {
            $this->view->getEnvironment()->addGlobal('session', $this->session->user); 
        }
        
        return $handler->handle($request);
    }
}
