<?php

namespace TailgateWeb\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Middlewares\Honeypot;

class HoneypotExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('getHiddenHoneypot', [$this, 'getHiddenHoneypot']),
        ];
    }

    public function getHiddenHoneypot()
    {
        return Honeypot::getHiddenField();
    }

}
