<?php

namespace TailgateWeb\Mailer;

class GroupInvite
{
    private $to;
    private $groupName;
    private $inviteCode;

    public function __construct($to, $groupName, $inviteCode)
    {
        $this->to = $to;
        $this->groupName = $groupName;
        $this->inviteCode = $inviteCode;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getGroupName()
    {
        return $this->groupName;
    }

    public function getInviteCode()
    {
        return $this->inviteCode;
    }
}