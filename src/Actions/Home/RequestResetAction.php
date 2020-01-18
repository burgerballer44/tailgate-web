<?php

namespace TailgateWeb\Actions\Home;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;
use TailgateWeb\Mailer\ResetPasswordEmail;

// request a password reset form
class RequestResetAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'request-reset.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/request-reset", ['email' => $parsedBody['email']]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'request-reset.twig', ['errors' => $data['errors']]);
        }

        if (!isset($data['data']['passwordResetToken'])) {
            $this->flash->addMessage('error', "Unable to set reset token. Please try again.");
            return $this->response->withHeader('Location', '/')->withStatus(302);
        }

        $template = new ResetPasswordEmail($parsedBody['email'], $data['data']['passwordResetToken']);

        if ($this->mailer->sendResetPasswordLink($this->request->getUri(), $template)) {
            $this->flash->addMessage('success', "Please check your email at {$parsedBody['email']} for further instructions.");
        }

        return $this->response->withHeader('Location', '/sign-in')->withStatus(302);
    }
}