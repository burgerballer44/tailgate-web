<?php

namespace TailgateWeb\Actions\Home;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// sign in form
class SignInAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'sign-in.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/token", [
            'grant_type' => 'password',
            'client_id' => $this->apiClient->config['clientId'],
            'client_secret' => $this->apiClient->config['clientSecret'],
            'username' => $parsedBody['email'],
            'password' => $parsedBody['password'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'sign-in.twig', ['errors' => $data['errors']]);
        }

        // set token data
        $data = json_decode($clientResponse->getBody(), true);

        if (!isset($data['access_token'])) {
            return $this->response->withHeader('Location', '/')->withStatus(302);
        }

        $this->session->set('tokens', [
            'access_token' => $data['access_token'],
            'expires' => strtotime('+' . $data['expires_in'] . ' seconds'),
            'refresh_token' => $data['refresh_token']
        ]);

        // now set user data
        $clientResponse = $this->apiClient->get("/v1/users/me");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'sign-in.twig', ['errors' => $data['errors']]);
        }
        
        $data = json_decode($clientResponse->getBody(), true);

        $this->session->set('user', $data['data']);

        return $this->response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}