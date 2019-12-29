<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// profile page
class ProfileAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        return $this->view->render($this->response, 'user/profile.twig');
    }
}