<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends AbstractController
{   
    /**
     * registration form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'user/register.twig');
    }

    /**
     * submit registration form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
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
        if ($this->sendConfirmationEmail($user)) {
            $this->flash->addMessage('success', "Thank you for registering. Please check your email at {$user['email']} to confirm your email address.");
        }

        return $response->withHeader('Location', '/sign-in')->withStatus(302);
    }

    /**
     * confirm the email from registration
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
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

    /**
     * profile page
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function profile(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'user/profile.twig');
    }

    /**
     * email update form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function email(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/users/me");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'user/email.twig');
        }

        $user = $data['data'];

        return $this->view->render($response, 'user/email.twig', compact('user'));
    }

    /**
     * submit email update form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function emailPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/users/me");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'user/email.twig');
        }

        $user = $data['data'];

        $clientResponse = $this->apiPatch("/v1/users/me/email", ['email' => $parsedBody['email']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'user/email.twig', [
                'errors' => $data['errors'],
                'user' => $user,
            ]);
        }

        $sessionUser = $this->session->get('user');

        $sessionUser['email'] = $parsedBody['email'];
        $this->session->set('user', $sessionUser);

        return $response->withHeader('Location', "/dashboard")->withStatus(302);
    }

    /**
     * view all users
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/admin/users");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/user/index.twig');
        }

        $users = $data['data'];
        return $this->view->render($response, 'admin/user/index.twig', compact('users'));
    }

    /**
     * view a single user
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/admin/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/user/view.twig');
        }

        $user = $data['data'];
        return $this->view->render($response, 'admin/user/view.twig', compact('user'));
    }

    /**
     * update user form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/admin/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/user/update.twig');
        }

        $user = $data['data'];

        return $this->view->render($response, 'admin/user/update.twig', compact('userId', 'user'));
    }

    /**
     * submit update user form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function updatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/admin/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/user/update.twig');
        }
        
        $user = $data['data'];

        $clientResponse = $this->apiPatch("/v1/admin/users/{$userId}", [
            'email' => $parsedBody['email'],
            'status' => $parsedBody['status'],
            'role' => $parsedBody['role'],
        ]);
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {

            return $this->view->render($response, 'admin/user/update.twig', [
                'errors' => $data['errors'],
                'userId' => $userId,
                'user' => $user,
            ]);
        }

        $sessionUser = $this->session->get('user');

        // if it's the logged in user, update the session info
        if ($sessionUser['userId'] == $user['userId']) {
            $sessionUser['email'] = $parsedBody['email'];
            $sessionUser['status'] = $parsedBody['status'];
            $sessionUser['role'] = $parsedBody['role'];
            $this->session->set('user', $sessionUser);
        }

        return $response->withHeader('Location', "/admin/users/{$userId}")->withStatus(302);
    }

    /**
     * delete a user
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiDelete("/v1/admin/users/{$userId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'admin/user/view.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', "/admin/users/{$userId}")->withStatus(302);
    }

    /**
     * resend the confirmation email for a user
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function resendConfirmation(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiGet("/v1/admin/users/{$userId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/user/view.twig', ['errors' => $data['errors']]);
        }

        $user = $data['data'];
        if ($this->sendConfirmationEmail($user)) {
            $this->flash->addMessage('success', "Email sent to {$user['email']}.");
        }

        return $response->withHeader('Location', "/admin/users/{$userId}")->withStatus(302);
    }

    /**
     * sends confirmation email
     * @param  [type] $user [description]
     * @return [type]       [description]
     */
    private function sendConfirmationEmail($user)
    {
        $emailParams = [
            'to'         => $user['email'],
            'subject'    => 'Confirm Tar Heel Tailgate Email Address',
            'template'   => 'confirm_email',
            'v:link'     => $this->mailer->getConfirmationLink($user['userId'], $user['email']),
            'o:tag'      => ['register'],
            'o:testmode' => $this->settings['mailgun_test_mode'],
        ];

        return $this->mailer->send($emailParams);
    }
}