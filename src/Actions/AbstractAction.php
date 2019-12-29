<?php

namespace TailgateWeb\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use TailgateWeb\Client\TailgateApiClientInterface;
use TailgateWeb\Mailer\MailerInterface;
use TailgateWeb\Session\SessionHelperInterface;

abstract class AbstractAction
{
    protected $request;
    protected $response;
    protected $args;
    protected $apiClient;
    protected $session;
    protected $view;
    protected $flash;

    public function __construct(
        TailgateApiClientInterface $apiClient,
        SessionHelperInterface $session,
        MailerInterface $mailer,
        Twig $view,
        Messages $flash
    ) {
        $this->apiClient = $apiClient;
        $this->session = $session;
        $this->mailer = $mailer;
        $this->view = $view;
        $this->flash = $flash;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        return $this->action();
    }

    abstract protected function action(): ResponseInterface;

    protected function respond()
    {
        return $this->response;
    }

    protected function respondWithData(array $data = [], int $code = 200)
    {
        $this->response->getBody()->write(json_encode(['data' => $data], JSON_PRETTY_PRINT));
        $this->response = $this->response->withHeader('Content-Type', 'application/json');
        return $this->response->withStatus($code);
    }
}
