<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TailgateWeb\Client\TailgateApiClientInterface;
use TailgateWeb\Session\SessionHelperInterface;

// set user information
class UpdateUserSessionMiddleware implements MiddlewareInterface
{
    protected $session;
    protected $apiClient;

    public function __construct(SessionHelperInterface $session, TailgateApiClientInterface $apiClient)
    {
        $this->session = $session;
        $this->apiClient = $apiClient;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {   
        if ($this->session->has('tokens')) {
            $apiResponse = $this->apiClient->get("/v1/users/me");
            $data = $apiResponse->getData();
            $this->session->set('user', $data['data']);
        }
       
        return $handler->handle($request);
    }
}
