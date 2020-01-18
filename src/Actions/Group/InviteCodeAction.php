<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// invite code form
class InviteCodeAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/invite-code.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/v1/groups/invite-code", ['inviteCode' => $parsedBody['invite_code']]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'group/invite-code.twig', ['errors' => $data['errors']]);
        }

        $this->flash->addMessage('success', "Successfully joined.");
        return $this->response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}