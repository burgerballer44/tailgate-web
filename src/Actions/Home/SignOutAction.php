<?php

namespace TailgateWeb\Actions\Home;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// sign out
class SignOutAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $this->session->destroy();
        return $this->response->withHeader('Location', '/')->withStatus(302);
    }
}