<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// email update form
class UpdateEmailAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        // get user
        $apiResponse = $this->apiClient->get("/v1/users/me");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'user/email.twig');
        }
        $user = $data['data'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'user/email.twig', compact('user'));
        }

       $parsedBody = $this->request->getParsedBody();

       $apiResponse = $this->apiClient->patch("/v1/users/me/email", ['email' => $parsedBody['email']]);
       $data = $apiResponse->getData();

       if ($apiResponse->hasErrors()) {
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