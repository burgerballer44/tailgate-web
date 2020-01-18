<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update member form
class UpdateMemberAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {    
        $memberTypes = ['Group-Admin' => 'Group-Admin', 'Group-Member' => 'Group-Member'];
        $allowMultiplePlayers = ['No', 'Yes'];

        extract($this->args);

        // get the group, and member
        $apiResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }
        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('memberId', $memberId);

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/update-member.twig', compact(
                'groupId',
                'memberId',
                'member',
                'memberTypes',
                'allowMultiplePlayers',
                'member'
            ));;
        }

        $apiResponse = $this->apiClient->patch("/v1/groups/{$groupId}/member/{$memberId}", [
            'groupId' => $groupId,
            'memberId' => $memberId,
            'groupRole' => $parsedBody['group_role'],
            'allowMultiple' => $parsedBody['allow_multiple']
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'group/update-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
                'member' => $member,
                'memberTypes' => $memberTypes,
                'allowMultiplePlayers' => $allowMultiplePlayers,
                'member' => $member
            ]);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}