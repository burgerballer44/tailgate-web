<?php

namespace TailgateWeb\Mailer;

use Mailgun\Mailgun;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\RouteParserInterface;
use TailgateWeb\Mailer\ConfirmationEmail;
use TailgateWeb\Mailer\GroupInvite;
use TailgateWeb\Mailer\MailerInterface;
use TailgateWeb\Mailer\ResetPasswordEmail;

class MailgunMailer implements MailerInterface
{
    private $mailgun;
    private $routeParser;
    private $logger;
    private $domain = '';
    private $mailgunTestMode = true;

    public function __construct(
        Mailgun $mailgun,
        RouteParserInterface $routeParser,
        LoggerInterface $logger,
        $domain,
        $mailgunTestMode
    ) {
        $this->mailgun = $mailgun;
        $this->routeParser = $routeParser;
        $this->logger = $logger;
        $this->domain = $domain;
        $this->mailgunTestMode = $mailgunTestMode;
    }

    public function sendConfirmationLink(UriInterface $uri, ConfirmationEmail $template)
    {   
        $emailParams = [
            'to'       => $template->getTo(),
            'subject'  => 'Confirm Tar Heel Tailgate Email Address',
            'template' => 'confirm_email',
            'v:link'   => $this->routeParser->fullUrlFor($uri, 'confirm', [], ['id' => $template->getUserId(), 'email' => $template->getEmail()]),
            'o:tag'    => ['register'],
        ];

        return $this->send($emailParams);
    }

    public function sendResetPasswordLink(UriInterface $uri, ResetPasswordEmail $template)
    {
        $emailParams = [
            'to'       => $template->getTo(),
            'subject'  => 'Reset Tar Heel Tailgate Password',
            'template' => 'reset_password',
            'v:link'   => $this->routeParser->fullUrlFor($uri, 'reset-password', ['token' => $template->getToken()]),
            'o:tag'    => ['reset'],
        ];

        return $this->send($emailParams);
    }

    public function sendGroupInvite(GroupInvite $template)
    {
        $emailParams = [
            'to'       => $template->getTo(),
            'subject'  => "Tar Heel Tailgate Invite to {$template->getGroupName()}",
            'template' => 'invite_code',
            'v:group'  => $template->getGroupName(),
            'v:code'   => $template->getInviteCode(),
            'o:tag'    => ['invite'],
        ];

        return $this->send($emailParams);
    }

    public function send($emailParams)
    {   
        $emailParams = array_merge($emailParams, [
            'from' => 'Tar Heel Tailgate <noreply@' . $this->domain . '>',
            'o:testmode' => $this->mailgunTestMode
        ]);

        try {
            $this->mailgun->messages()->send($this->domain, $emailParams);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error("Mailgun email failed to send to {$emailParams['to']}");
            $this->logger->error($e->getMessage());
        }

        return false;
    }
}