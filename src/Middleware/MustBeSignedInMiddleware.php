<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TailgateWeb\Session\SessionHelperInterface;

// must be signed in
class MustBeSignedInMiddleware implements MiddlewareInterface
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
        // if there is no signed in user
        if (!$this->session->has('user')) {
            
            // store the uri they were trying to access
            $this->session->set('referrer', $request->getUri());  

            $response = $this->responseFactory->createResponse();
            return $response->withHeader('Location', '/sign-in')->withStatus(302);
        }

        // // if they were trying to access a page prior to being redirected
        // if ($this->session->has(referrer)) {

        //     $uri = $this->session->get('referrer');
        //     $this->session->delete('referrer');
            
        //     $response = $this->responseFactory->createResponse();
        //     return $response->withHeader('Location', $uri->getPath() . "?" . $uri->getQuery())->withStatus(302);

        // }
       
        return $handler->handle($request);
    }
}
