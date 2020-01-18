<?php

namespace TailgateWeb\Actions\Home;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// sign in form
class SignInAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'sign-in.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/token", [
            'grant_type' => 'password',
            'client_id' => $this->apiClient->config['clientId'],
            'client_secret' => $this->apiClient->config['clientSecret'],
            'username' => $parsedBody['email'],
            'password' => $parsedBody['password'],
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'sign-in.twig', ['errors' => $data['errors']]);
        }

        // set token data
        $this->session->set('tokens', [
            'access_token' => $data['access_token'],
            'expires' => strtotime('+' . $data['expires_in'] . ' seconds'),
            'refresh_token' => $data['refresh_token']
        ]);

        // now set user data
        $apiResponse = $this->apiClient->get("/v1/users/me");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'sign-in.twig', ['errors' => $data['errors']]);
        }
        
        $this->session->set('user', $data['data']);

        return $this->response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}