<?php

namespace TailgateWeb\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EnvironmentExtension extends AbstractExtension
{
    private $prodMode;

    public function __construct(bool $prodMode)
    {
        $this->prodMode = $prodMode;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('prodMode', [$this, 'prodMode']),
        ];
    }

    public function prodMode()
    {
        return $this->prodMode;
    }
}
