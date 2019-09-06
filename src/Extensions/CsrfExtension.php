<?php

namespace TailgateWeb\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CsrfExtension extends AbstractExtension
{
    private $csrf;

    public function __construct($csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('getCsrfFields', [$this, 'getCsrfFields']),
        ];
    }

    public function getCsrfFields()
    {
        $nameKey = $this->csrf->getTokenNameKey();
        $valueKey = $this->csrf->getTokenValueKey();
        $name = $this->csrf->getTokenName();
        $value = $this->csrf->getTokenValue();

        $fieldsString = "
            <input type='hidden' name='$nameKey' value='$name'>
            <input type='hidden' name='$valueKey' value='$value'>
        ";

        return $fieldsString;
    }

}
