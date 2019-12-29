<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;
use TailgateWeb\Mailer\ConfirmationEmail;

// resend the confirmation email for a use
class ResendConfirmationAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        extract($this->args);

        $clientResponse = $this->apiClient->get("/v1/admin/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($this->response, 'admin/user/view.twig', ['errors' => $data['errors']]);
        }

        $user = $data['data'];
        
        $template = new ConfirmationEmail($user['email'], $user['userId'], $user['email']);

        if ($this->mailer->sendConfirmationLink($this->request->getUri(), $template)) {
            $this->flash->addMessage('success', "Email sent to {$user['email']}.");
        }

        return $this->response->withHeader('Location', "/admin/users/{$userId}")->withStatus(302);
    }
}