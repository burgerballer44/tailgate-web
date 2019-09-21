<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends AbstractController
{   
    // view all users
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/users");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'user/index.twig', ['errors' => $data['errors']]);
        }

        $users = $data['data'];
        return $this->view->render($response, 'user/index.twig', compact('users'));
    }

    // view a single user
    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $userId = $args['userId'];

        $clientResponse = $this->apiGet("/v1/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'user/view.twig', ['errors' => $data['errors']]);
        }

        $user = $data['data'];
        return $this->view->render($response, 'user/view.twig', compact('user'));
    }

    // confirm the email from registration
    public function confirm(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $params = $request->getQueryParams();
        $userId = $params['id'];
        $email = $params['email'];

        $clientResponse = $this->apiPatch("/activate/{$userId}", ['email' => $email]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true); 

            $error = isset($data['errors']['userId']) ? implode(', ', $data['errors']['userId']) : 'An unspecified error occured while trying to confirm your email address.';

            $this->flash->addMessage('error', $error);
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $this->flash->addMessage('success', "Thank you for confirming.");
        return $response->withHeader('Location', '/sign-in')->withStatus(302);
    }

    // registration form
    public function register(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'user/register.twig');
    }

    // submit registration form
    public function registerPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/register", [
            'email' => $parsedBody['email'],
            'password' => $parsedBody['password'],
            'confirmPassword' => $parsedBody['confirm_password'],
        ]);
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'user/register.twig', ['errors' => $data['errors']]);
        }

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

    // update user form
    public function update(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $userId = $args['userId'];

        $clientResponse = $this->apiGet("/v1/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'user/update.twig', ['errors' => $data['errors'], 'userId' => $userId]);
        }

        $user = $data['data'];

        return $this->view->render($response, 'user/update.twig', compact('userId', 'user'));
    }

    // submit update user form
    public function updatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $userId = $args['userId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);
        $user = $data['data'];

        $clientResponse = $this->apiPatch("/v1/users/{$userId}", [
            'email' => $parsedBody['email'],
            'status' => $parsedBody['status'],
            'role' => $parsedBody['role'],
        ]);
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {

            return $this->view->render($response, 'user/update.twig', [
                'errors' => $data['errors'],
                'userId' => $userId,
                'user' => $user,
            ]);
        }

        return $response->withHeader('Location', "/user/{$userId}")->withStatus(302);
    }

    // delete a user
    public function delete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $userId = $args['userId'];

        $clientResponse = $this->apiDelete("/v1/users/{$userId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'user/update.twig', ['errors' => $data['errors'], 'userId' => $userId]);
        }

        return $response->withHeader('Location', "/user")->withStatus(302);
    }

    // email update form
    public function email(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $userId = $args['userId'];

        $clientResponse = $this->apiGet("/v1/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);
        $user = $data['data'];

        return $this->view->render($response, 'user/email.twig', compact('userId', 'user'));
    }

    // submit email update form
    public function emailPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $userId = $args['userId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);
        $user = $data['data'];

        $clientResponse = $this->apiPatch("/v1/users/{$userId}/email", ['email' => $parsedBody['email']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'user/email.twig', [
                'errors' => $data['errors'],
                'userId' => $userId,
                'user' => $user,
            ]);
        }

        return $response->withHeader('Location', "/user/{$userId}")->withStatus(302);
    }

    // password form
    public function password(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $userId = $args['userId'];
        return $this->view->render($response, 'user/password.twig', compact('userId'));
    }

    // submit password form
    public function passwordPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $userId = $args['userId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPatch("/v1/users/{$userId}/password", [
            'password' => $parsedBody['password'],
            'confirmPassword' => $parsedBody['confirm_password']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'user/password.twig', ['errors' => $data['errors'],'userId' => $userId]);
        }

        return $response->withHeader('Location', "/user/{$userId}")->withStatus(302);
    }
}