<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TailgateWeb\Session\SessionHelperInterface;

// must be signed out
class MustBeSignedOutMiddleware implements MiddlewareInterface
{
    protected $session;
    protected $responseFactory;

    public function __construct(SessionHelperInterface $session, ResponseFactoryInterface $responseFactory)
    {
        $this->session = $session;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->session->has('user')) {
            $response = $this->responseFactory->createResponse();
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }
        
        return $handler->handle($request);
    }
}
