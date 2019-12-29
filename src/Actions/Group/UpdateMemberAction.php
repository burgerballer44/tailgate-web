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

        if ('POST' != $this->request->getMethod()) {
            $clientResponse = $this->apiClient->get("/v1/groups/{$groupId}");
            $data = json_decode($clientResponse->getBody(), true);

            if ($clientResponse->getStatusCode() >= 400) {
                return $this->view->render($this->response, 'group/update-member.twig', [
                    'errors' => $data['errors'],
                    'groupId' => $groupId,
                    'memberId' => $memberId,
                ]);
            }

            $group = $data['data'];
            $member = collect($group['members'])->firstWhere('memberId', $memberId);

            return $this->view->render($this->response, 'group/update-member.twig', compact(
                'groupId',
                'memberId',
                'member',
                'memberTypes',
                'allowMultiplePlayers'
            ));;
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('memberId', $memberId);

        $clientResponse = $this->apiClient->patch("/v1/groups/{$groupId}/member/{$memberId}", [
            'groupId' => $groupId,
            'memberId' => $memberId,
            'groupRole' => $parsedBody['group_role'],
            'allowMultiple' => $parsedBody['allow_multiple']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'group/update-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
                'member' => $member,
                'memberTypes' => $memberTypes,
                'allowMultiplePlayers' => $allowMultiplePlayers
            ]);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}