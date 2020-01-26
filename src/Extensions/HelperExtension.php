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
            new TwigFunction('isSignedIn', [$this, 'isSignedIn']),
            new TwigFunction('isAdmin', [$this, 'isAdmin']),
            new TwigFunction('isGroupAdmin', [$this, 'isGroupAdmin']),
            new TwigFunction('isGroupOwner', [$this, 'isGroupOwner']),
        ];
    }

    public function isSignedIn()
    {
        return $this->session->has('user');
    }

    public function isAdmin()
    {
        return isset($this->session->get('user')['role']) && $this->session->get('user')['role'] == 'Admin';
    }

    public function isGroupAdmin($member)
    {
        return 'Group-Admin' == $member['role'];
    }

    public function isGroupOwner($group, $member)
    {
        return $group['ownerId'] == $member['userId'];
    }

}
