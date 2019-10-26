<?php

namespace TailgateWeb\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HelperExtension extends AbstractExtension
{
    private $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('isAdmin', [$this, 'isAdmin']),
            new TwigFunction('isGroupAdmin', [$this, 'isGroupAdmin']),
        ];
    }

    public function isAdmin()
    {
        return isset($this->session->get('user')['role']) && $this->session->get('user')['role'] == 'Admin';
    }

    public function isGroupAdmin($member)
    {
        return $member['role'] == 'Group-Admin';
    }

}
