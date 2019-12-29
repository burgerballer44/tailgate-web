<?php

namespace TailgateWeb\Actions\Home;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// go to home page
class HomeAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        return $this->view->render($this->response, 'index.twig');
    }
}