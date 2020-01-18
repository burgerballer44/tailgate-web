<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;
use TailgateWeb\Mailer\ConfirmationEmail;
use Gregwar\Captcha\CaptchaBuilder;

// registration form
class RegisterAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        // create an inline html captcha to use in the form
        $builder = new CaptchaBuilder;
        $builder->build();
        $captcha =  $builder->inline();

        if ('GET' == $this->request->getMethod()) {
            // set the captcha phrase in the session so we remember to check against it
            $this->session->set('phrase', $builder->getPhrase());
            return $this->view->render($this->response, 'user/register.twig', compact('captcha'));
        }

        $parsedBody = $this->request->getParsedBody();

        // validate the captcha phrase
        if (!$this->session->has('phrase') || $this->session->get('phrase') != $parsedBody['phrase']) {
            $errors = [];
            $errors['phrase'] = ['Captcha incorrect. Please try again.'];
            $this->session->set('phrase', $builder->getPhrase());
            return $this->view->render($this->response, 'user/register.twig', compact('captcha', 'errors'));
        }

        // remove captcha phrase since it is no longer needed
        $this->session->delete('phrase');
        
        $apiResponse = $this->apiClient->post("/register", [
            'email' => $parsedBody['email'],
            'password' => $parsedBody['password'],
            'confirmPassword' => $parsedBody['confirm_password'],
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'user/register.twig', [
                'captcha' => $captcha,
                'errors' => $data['errors']
            ]);
        }

        $user = $data['data'];

        $template = new ConfirmationEmail($user['email'], $user['userId'], $user['email']);

        if ($this->mailer->sendConfirmationLink($this->request->getUri(), $template)) {
            $this->flash->addMessage('success', "Thank you for registering. Please check your email at {$user['email']} to confirm your email address.");
        }

        return $this->response->withHeader('Location', '/sign-in')->withStatus(302);
    }
}