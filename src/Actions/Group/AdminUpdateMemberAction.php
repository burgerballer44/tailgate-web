<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update member form for amdin
class AdminUpdateMemberAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {    
        $memberTypes = ['Group-Admin' => 'Group-Admin', 'Group-Member' => 'Group-Member'];
        $allowMultiplePlayers = ['No', 'Yes'];

        extract($this->args);

        // get the group and member
        $apiResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/group/update-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
            ]);
        }
        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('memberId', $memberId);

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/group/update-member.twig', compact(
                'groupId',
                'memberId',
                'member',
                'memberTypes',
                'allowMultiplePlayers'
            ));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->patch("/v1/admin/groups/{$groupId}/member/{$memberId}", [
            'groupId' => $groupId,
            'memberId' => $memberId,
            'groupRole' => $parsedBody['group_role'],
            'allowMultiple' => $parsedBody['allow_multiple']
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/group/update-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
                'member' => $member,
                'memberTypes' => $memberTypes,
                'allowMultiplePlayers' => $allowMultiplePlayers
            ]);
        }

        return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}