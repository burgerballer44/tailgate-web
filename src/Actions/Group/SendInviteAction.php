<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;
use TailgateWeb\Mailer\GroupInvite;

// send invite form
class SendInviteAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        // get the group, and member
        $apiResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }
        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('userId', $this->session->get('user')['userId']);

        // must be group admin
        if ('Group-Admin' != $member['role']) {
            $this->flash->addMessage('error', 'must be group admin');
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/send-invite.twig', compact('group', 'groupId'));
        }

        $parsedBody = $this->request->getParsedBody();

        if (!filter_var($parsedBody['email'], FILTER_VALIDATE_EMAIL)) {
            $errors = [];
            $errors['email'] = ['Email must be a valid email address'];
            return $this->view->render($this->response, 'group/send-invite.twig', compact('group', 'groupId', 'errors'));
        }

        $template = new GroupInvite($parsedBody['email'], $group['name'], $group['inviteCode']);

        if ($this->mailer->sendGroupInvite($template)) {
            $this->flash->addMessage('success', "Invitiation sent to {$parsedBody['email']}.");
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}