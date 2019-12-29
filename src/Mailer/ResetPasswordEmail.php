<?php

namespace TailgateWeb\Mailer;

class ResetPasswordEmail
{
    private $to;
    private $token;

    public function __construct($to, $token)
    {
        $this->to = $to;
        $this->token = $token;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getToken()
    {
        return $this->token;
    }
}