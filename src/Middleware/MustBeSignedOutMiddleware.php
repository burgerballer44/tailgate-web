<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

// must be signed out
class MustBeSignedOutMiddleware
{
    protected $session;
    protected $responseFactory;

    public function __construct($session, ResponseFactoryInterface $responseFactory)
    {
        $this->session = $session;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isset($this->session->user)) {
            $response = $this->responseFactory->createResponse();
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }
        
        return $handler->handle($request);
    }
}
