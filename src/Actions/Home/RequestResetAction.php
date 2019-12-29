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
        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'request-reset.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/request-reset", ['email' => $parsedBody['email']]);
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
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