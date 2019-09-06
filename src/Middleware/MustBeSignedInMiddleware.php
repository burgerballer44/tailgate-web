<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

// must be signed in
class MustBeSignedInMiddleware
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
        // if there is no signed in user
        if (!isset($this->session->user)) {
            
            // store the uri they were trying to access
            $this->session->referrer = $request->getUri();  

            $response = $this->responseFactory->createResponse();
            return $response->withHeader('Location', '/sign-in')->withStatus(302);
        }

        // if they were trying to access a page prior to being redirected
        if (isset($this->session->referrer)) {

            $uri = $this->session->referrer;
            unset($this->session->referrer);
            
            $response = $this->responseFactory->createResponse();
            return $response->withHeader('Location', $uri->getPath() . "?" . $uri->getQuery())->withStatus(302);

        }
       
        return $handler->handle($request);
    }
}
