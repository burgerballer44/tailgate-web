<?php

namespace TailgateWeb\Extensions;

use Psr\Http\Message\ServerRequestInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ParsedBodyExtension extends AbstractExtension
{
    private $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;

    }
    
    public function getGlobals()
    {
        return [
            'old' => $this->request->getParsedBody(),
        ];
    }
}
