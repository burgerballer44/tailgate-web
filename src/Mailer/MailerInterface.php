<?php

namespace TailgateWeb\Mailer;

use Psr\Http\Message\UriInterface;
use TailgateWeb\Mailer\ConfirmationEmail;
use TailgateWeb\Mailer\GroupInvite;
use TailgateWeb\Mailer\ResetPasswordEmail;

interface MailerInterface
{
    public function sendConfirmationLink(UriInterface $uri, ConfirmationEmail $template);
    public function sendResetPasswordLink(UriInterface $uri, ResetPasswordEmail $template);
    public function sendGroupInvite(GroupInvite $template);
}