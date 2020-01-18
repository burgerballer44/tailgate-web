<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;
use TailgateWeb\Mailer\ConfirmationEmail;

// confirm the email from registration
class ConfirmEmailAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $params = $this->request->getQueryParams();
        $userId = $params['id'];
        $email = $params['email'];

        $apiResponse = $this->apiClient->patch("/activate/{$userId}", ['email' => $email]);
        $data = $apiResponse->getData(); 

        if ($apiResponse->hasErrors()) {
            $error = isset($data['errors']['userId']) ? implode(', ', $data['errors']['userId']) : 'An unspecified error occured while trying to confirm your email address.';
            $this->flash->addMessage('error', $error);
            return $this->response->withHeader('Location', '/')->withStatus(302);
        }

        $this->flash->addMessage('success', "Thank you for confirming.");
        return $this->response->withHeader('Location', '/sign-in')->withStatus(302);
    }
}