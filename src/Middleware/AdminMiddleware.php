<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Flash\Messages;
use TailgateWeb\Session\SessionHelperInterface;

class AdminMiddleware implements MiddlewareInterface
{   
    protected $session;
    protected $flash;
    protected $responseFactory;

    public function __construct(SessionHelperInterface $session, Messages $flash, ResponseFactoryInterface $responseFactory)
    {
        $this->session = $session;
        $this->flash = $flash;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->session->get('user');

        if ('Admin' != $user['role']) {
            $response = $this->responseFactory->createResponse();
            $this->flash->addMessage('error', "Invalid Permissions");
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
