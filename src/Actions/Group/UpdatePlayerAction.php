<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update player form
class UpdatePlayerAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {    
        extract($this->args);

        // get the group,  player, and member
        $apiResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }
        $group = $data['data'];
        $player = collect($group['players'])->firstWhere('playerId', $playerId);
        $members = collect($group['members'])->flatMap(function($member){
            return [$member['memberId'] => $member['email']];
        })->toArray();
        $memberId = collect($group['players'])->firstWhere('playerId', $playerId)['memberId'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'group/update-player.twig', compact(
                'groupId',
                'playerId',
                'members',
                'memberId',
                'group',
                'player'
            ));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->patch("/v1/groups/{$groupId}/player/{$playerId}", ['memberId' => $parsedBody['member_id']]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'group/update-player.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'playerId' => $playerId,
                'members' => $members,
                'memberId' => $memberId,
                'group' => $group,
                'player' => $player
            ]);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}