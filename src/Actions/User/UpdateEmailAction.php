<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// email update form
class UpdateEmailAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        if ('POST' != $this->request->getMethod()) {
            $clientResponse = $this->apiClient->get("/v1/users/me");
            $data = json_decode($clientResponse->getBody(), true);

            if ($clientResponse->getStatusCode() >= 400) {
                return $this->view->render($this->response, 'user/email.twig');
            }

            $user = $data['data'];

            return $this->view->render($this->response, 'user/email.twig', compact('user'));
        }

       $parsedBody = $this->request->getParsedBody();

       $clientResponse = $this->apiClient->get("/v1/users/me");
       $data = json_decode($clientResponse->getBody(), true);

       if ($clientResponse->getStatusCode() >= 400) {
           return $this->view->render($this->response, 'user/email.twig');
       }

       $user = $data['data'];

       $clientResponse = $this->apiClient->patch("/v1/users/me/email", ['email' => $parsedBody['email']]);

       if ($clientResponse->getStatusCode() >= 400) {
           $data = json_decode($clientResponse->getBody(), true);
           return $this->view->render($this->response, 'user/email.twig', [
               'errors' => $data['errors'],
               'user' => $user,
           ]);
       }

       $sessionUser = $this->session->get('user');

       $sessionUser['email'] = $parsedBody['email'];
       $this->session->set('user', $sessionUser);

       return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
    }
}