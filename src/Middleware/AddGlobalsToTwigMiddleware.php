<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// add session stuff to view
class AddGlobalsToTwigMiddleware implements MiddlewareInterface
{
    protected $session;
    protected $view;

    public function __construct($session, $view)
    {
        $this->session = $session;
        $this->view = $view;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {   
        if ($this->session->exists('user')) {
            $this->view->getEnvironment()->addGlobal('session', $this->session->get('user')); 
        }
        
        return $handler->handle($request);
    }
}
