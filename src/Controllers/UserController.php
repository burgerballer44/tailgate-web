<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends AbstractController
{
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet('/v1/users');

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'user/index.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $data = json_decode($clientResponse->getBody(), true);
        $users = $data['data'];

        return $this->view->render($response, 'user/index.twig', compact('users'));
    }


    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $userId = $args['userId'];

        $clientResponse = $this->apiGet('/v1/users/' . $userId);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'user/view.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $data = json_decode($clientResponse->getBody(), true);
        $user = $data['data'];

        return $this->view->render($response, 'user/view.twig', compact('user'));
    }

    public function confirm(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $params = $request->getQueryParams();
        $userId = $params['id'];
        $email = $params['email'];

        $clientResponse = $this->apiPatch("/activate/{$userId}", ['email' => $email]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'sign-in.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $this->flash->addMessageNow('success', "Thank you for confirming.");

        return $this->view->render($response, 'sign-in.twig');
    }

    public function register(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'user/register.twig');
    }

    public function registerPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/register', [
            'email' => $parsedBody['email'],
            'password' => $parsedBody['password'],
            'confirm_password' => $parsedBody['confirm_password'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'user/register.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $data = json_decode($clientResponse->getBody(), true);
        $user = $data['data'];

        $emailParams = [
            'to'         => $user['email'],
            'subject'    => 'Confirm Tar Heel Tailgate Email Address',
            'template'   => 'confirm_email',
            'v:link'     => $this->mailer->getConfirmationLink($user['userId'], $user['email']),
            'o:tag'      => ['register'],
            'o:testmode' => $this->settings['mailgun_test_mode'],
        ];

        if ($this->mailer->send($emailParams)) {
            $this->flash->addMessage('success', "Thank you for registering. Please check your email at {$user['email']} to confirm your email address.");
        }

        return $response->withHeader('Location', '/sign-in')->withStatus(302);
    }
}