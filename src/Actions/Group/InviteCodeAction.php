<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// invite code form
class InviteCodeAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        if ('POST' != $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/invite-code.twig');
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/v1/groups/invite-code", ['inviteCode' => $parsedBody['invite_code']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'group/invite-code.twig', ['errors' => $data['errors']]);
        }

        $this->flash->addMessage('success', "Successfully joined.");
        return $this->response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}