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

        // get user
        $apiResponse = $this->apiClient->get("/v1/admin/users/{$userId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/user/view.twig', ['errors' => $data['errors']]);
        }

        $user = $data['data'];
        
        $template = new ConfirmationEmail($user['email'], $user['userId'], $user['email']);

        if ($this->mailer->sendConfirmationLink($this->request->getUri(), $template)) {
            $this->flash->addMessage('success', "Email sent to {$user['email']}.");
        } else {
            $this->flash->addMessage('error', "Email failed to send.");
        }

        return $this->response->withHeader('Location', "/admin/users/{$userId}")->withStatus(302);
    }
}