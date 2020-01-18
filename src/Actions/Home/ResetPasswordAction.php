<?php

namespace TailgateWeb\Actions\Home;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;
use TailgateWeb\Mailer\ResetPasswordEmail;

// reset password
class ResetPasswordAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        extract($this->args);
        
        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'reset-password.twig', ['token' => $token]);
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->patch("/reset-password", [
            'passwordResetToken' => $token,
            'password' => $parsedBody['password'],
            'confirmPassword' => $parsedBody['confirm_password']
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'reset-password.twig', ['errors' => $data['errors'], 'token' => $token]);
        }

        $this->flash->addMessage('success', "Success!");

        return $this->response->withHeader('Location', "/sign-in")->withStatus(302);
    }
}