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

    /**
     * request a password reset form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function requestReset(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'request-reset.twig');
    }

    /**
     * request a password reset form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function requestResetPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/request-reset", ['email' => $parsedBody['email']]);
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'request-reset.twig', ['errors' => $data['errors']]);
        }

        if (!isset($data['data']['passwordResetToken'])) {
            $this->flash->addMessage('error', "Unable to set reset token. Please try again.");
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $emailParams = [
            'to'         => $parsedBody['email'],
            'subject'    => 'Reset Tar Heel Tailgate Password',
            'template'   => 'reset_password',
            'v:link'     => $this->mailer->getResetPasswordLink($data['data']['passwordResetToken']),
            'o:tag'      => ['reset'],
            'o:testmode' => $this->settings['mailgun_test_mode'],
        ];

        if ($this->mailer->send($emailParams)) {
            $this->flash->addMessage('success', "Please check your email at {$parsedBody['email']} for further instructions.");
        }

        return $response->withHeader('Location', '/sign-in')->withStatus(302);
    }

    /**
     * password form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function password(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        return $this->view->render($response, 'reset-password.twig', ['token' => $token]);
    }

    /**
     * submit password form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function passwordPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPatch("/reset-password", [
            'passwordResetToken' => $token,
            'password' => $parsedBody['password'],
            'confirmPassword' => $parsedBody['confirm_password']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'reset-password.twig', ['errors' => $data['errors'], 'token' => $token]);
        }

        $this->flash->addMessage('success', "Success!");

        return $response->withHeader('Location', "/sign-in")->withStatus(302);
    }
}