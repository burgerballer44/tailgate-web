<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view group
class ViewGroupAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        $group = [];
        $member = [];
        $season = [];
        $gridHtml = '';

        extract($this->args);

        // get the group and determine if the user is a member
        $clientResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('userId', $this->session->get('user')['userId']);
        if (!$member) {
            $this->flash->addMessage('error', 'Unable to determine if you are a member of the group.');
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        // if the group is following a team then get all the games for the season they are following
        if (isset($group['follow']['seasonId'])) {
            $seasonId = $group['follow']['seasonId'];
            $clientResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
            $data = json_decode($clientResponse->getBody(), true);
            if ($clientResponse->getStatusCode() >= 400) {
                return $this->view->render($this->response, 'admin/season/view.twig', ['errors' => $data['errors']]);
            }
            $season = $data['data'];
        
            // $gridHtml = $this->container->get('scoring')->generate($group, $season, [])->getHtml();
        }


        return $this->view->render($this->response, 'group/view.twig', compact('group', 'groupId', 'member', 'season', 'gridHtml'));
    }
}