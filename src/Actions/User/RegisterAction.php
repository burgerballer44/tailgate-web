<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;
use TailgateWeb\Mailer\ConfirmationEmail;

// registration form
class RegisterAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'user/register.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/register", [
            'email' => $parsedBody['email'],
            'password' => $parsedBody['password'],
            'confirmPassword' => $parsedBody['confirm_password'],
        ]);
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($this->response, 'user/register.twig', ['errors' => $data['errors']]);
        }

        $user = $data['data'];

        $template = new ConfirmationEmail($user['email'], $user['userId'], $user['email']);

        if ($this->mailer->sendConfirmationLink($this->request->getUri(), $template)) {
            $this->flash->addMessage('success', "Thank you for registering. Please check your email at {$user['email']} to confirm your email address.");
        }

        return $this->response->withHeader('Location', '/sign-in')->withStatus(302);
    }
}