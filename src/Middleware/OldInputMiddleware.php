<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

// add form stuff to view
class OldInputMiddleware
{
    protected $view;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {   
        $view = $this->view->getEnvironment()->addGlobal('old', $request->getParsedBody());
        return $handler->handle($request);
    }
}
