<?php

namespace TailgateWeb\Mailer;

class ConfirmationEmail
{
    private $to;
    private $userId;
    private $email;

    public function __construct($to, $userId, $email)
    {
        $this->to = $to;
        $this->userId = $userId;
        $this->email = $email;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getEmail()
    {
        return $this->email;
    }
}