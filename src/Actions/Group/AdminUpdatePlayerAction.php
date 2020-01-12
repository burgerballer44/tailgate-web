<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// admin update player form
class AdminUpdatePlayerAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {    
        extract($this->args);

        if ('POST' != $this->request->getMethod()) {
            $clientResponse = $this->apiClient->get("/v1/groups/{$groupId}");
            $data = json_decode($clientResponse->getBody(), true);

            if ($clientResponse->getStatusCode() >= 400) {
                return $this->view->render($this->response, 'admin/group/update-player.twig', [
                    'errors' => $data['errors'],
                    'groupId' => $groupId,
                    'playerId' => $playerId,
                ]);
            }

            $group = $data['data'];
            $members = collect($group['members'])->flatMap(function($member){
                return [$member['memberId'] => $member['email']];
            })->toArray();
            $memberId = collect($group['players'])->firstWhere('playerId', $playerId)['memberId'];

            return $this->view->render($this->response, 'admin/group/update-player.twig', compact(
                'groupId',
                'playerId',
                'members',
                'memberId'
            ));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $members = collect($group['members'])->flatMap(function($member){
            return [$member['memberId'] => $member['email']];
        })->toArray();
        $memberId = collect($group['players'])->firstWhere('playerId', $playerId)['memberId'];

        $clientResponse = $this->apiClient->patch("/v1/groups/{$groupId}/player/{$playerId}", ['memberId' => $parsedBody['member_id']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($this->response, 'admin/group/update-player.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'playerId' => $playerId,
                'members' => $members,
                'memberId' => $memberId
            ]);
        }

        return $this->response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}