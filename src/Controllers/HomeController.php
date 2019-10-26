<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends AbstractController
{   
    /**
     * go to home page
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function home(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'index.twig');
    }

    /**
     * sign in form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function signIn(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'sign-in.twig');
    }

    /**
     * submit sign in form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function signInPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/token", [
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

        // set token data
        $data = json_decode($clientResponse->getBody(), true);

        if (!isset($data['access_token'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $this->session->set('tokens', [
            'access_token' => $data['access_token'],
            'expires' => strtotime('+' . $data['expires_in'] . ' seconds'),
            'refresh_token' => $data['refresh_token']
        ]);

        // now set user data
        $clientResponse = $this->apiGet("/v1/users/me");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'sign-in.twig', ['errors' => $data['errors']]);
        }
        
        $data = json_decode($clientResponse->getBody(), true);

        $this->session->set('user', $data['data']);

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    /**
     * sign out
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function signOut(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $this->session->destroy();
        return $response->withHeader('Location', '/')->withStatus(302);
    }

}