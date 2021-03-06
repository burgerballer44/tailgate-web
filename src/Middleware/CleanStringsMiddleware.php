<?php

namespace TailgateWeb\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CleanStringsMiddleware implements MiddlewareInterface
{
    private $fieldsToIgnore = [
        'password',
        'confirm_password',
    ];

    // trim strings and set empty to null
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {   
        $method = strtoupper($request->getMethod());

        if ('POST' == $method) {
            $contents = $request->getParsedBody();

            array_walk_recursive($contents, function(&$postVariable, $key) {
                if (is_string($postVariable) && !in_array($key, $this->fieldsToIgnore)) {
                    $postVariable = trim($postVariable);
                }
                $postVariable = ($postVariable === "") ? null : $postVariable;
            });

            $request = $request->withParsedBody($contents);
        }

        return $handler->handle($request);

    }
}
