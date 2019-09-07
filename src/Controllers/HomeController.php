<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends AbstractController
{
    public function home(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'index.twig');
    }

    public function signIn(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'sign-in.twig');
    }

    public function signInPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/token', [
            'grant_type' => 'password',
            'client_id' => $this->settings['client_id'],
            'client_secret' => $this->settings['client_secret'],
            'email' => $parsedBody['email'],
            'password' => $parsedBody['password'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'sign-in.twig', ['errors' => $data['errors']]);
        }

        $data = json_decode($clientResponse->getBody(), true);

        $session = $this->session;
        $session->user = [
            'email' => $parsedBody['email']
        ];
        $session->tokens = [
            'access_token' => $data['access_token'],
            'expires' => strtotime('+' . $data['expires_in'] . ' seconds'),
            'refresh_token' => $data['refresh_token']
        ];

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function signOut(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $this->session->destroy();
        return $response->withHeader('Location', '/')->withStatus(302);
    }

}