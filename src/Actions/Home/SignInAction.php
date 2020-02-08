<?php

namespace TailgateWeb\Actions\Home;

use Psr\Http\Message\ResponseInterface;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use TailgateWeb\Actions\AbstractAction;
use TailgateWeb\Client\ApiCredentials;
use TailgateWeb\Client\TailgateApiClientInterface;
use TailgateWeb\Mailer\MailerInterface;
use TailgateWeb\Session\SessionHelperInterface;

// sign in form
class SignInAction extends AbstractAction
{   
    private $credentials;

    public function __construct(
        TailgateApiClientInterface $apiClient,
        SessionHelperInterface $session,
        MailerInterface $mailer,
        Twig $view,
        Messages $flash,
        ApiCredentials $credentials
    ) {
        parent::__construct($apiClient, $session, $mailer, $view, $flash);
        $this->credentials = $credentials;
    }

    public function action() : ResponseInterface
    {
        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'sign-in.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/token", [
            'grant_type' => 'password',
            'client_id' => $this->credentials->getClientId(),
            'client_secret' => $this->credentials->getClientSecret(),
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