<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GroupController extends AbstractController
{
    /**
     * invite code form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function inviteCode(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'group/invite-code.twig');
    }

    /**
     * submit the invite code form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function inviteCodePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();
        $clientResponse = $this->apiPost("/v1/groups/invite-code", ['inviteCode' => $parsedBody['invite_code']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'group/invite-code.twig', ['errors' => $data['errors']]);
        }

        $this->flash->addMessage('success', "Successfully joined.");
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    /**
     * send invite form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function sendInvite(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        $group = $data['data'];

        return $this->view->render($response, 'group/send-invite.twig', compact('group', 'groupId'));
    }

    /**
     * submit the send invite form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function sendInvitePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        $group = $data['data'];

        if (!filter_var($parsedBody['email'], FILTER_VALIDATE_EMAIL)) {
            $errors = [];
            $errors['email'] = ['Email must be a valid email address'];
            return $this->view->render($response, 'group/send-invite.twig', compact('group', 'groupId', 'errors'));
        }

        $emailParams = [
            'to'         => $parsedBody['email'],
            'subject'    => "Tar Heel Tailgate Invite to {$group['name']}",
            'template'   => 'invite_code',
            'v:group'     => $group['name'],
            'v:code'     => $group['inviteCode'],
            'o:tag'      => ['invite'],
            'o:testmode' => $this->settings['mailgun_test_mode'],
        ];

        if ($this->mailer->send($emailParams)) {
            $this->flash->addMessage('success', "Invitiation sent to {$parsedBody['email']}.");
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    /**
     * create group form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'group/create.twig');
    }

    /**
     * submit the create group form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function createPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/groups", [
            'name' => $parsedBody['name'],
            'userId' => $this->session->get('user')['userId'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'group/create.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    /**
     * view a group
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $group = [];
        $member = [];
        $season = [];
        $gridHtml = '';

        extract($args);

        // get the group and determine if the user is a member
        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('userId', $this->session->get('user')['userId']);
        if (!$member) {
            $this->flash->addMessage('error', 'Unable to determine if you are a member of the group.');
            return $response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        // if the group is following a team then get all the games for the season they are following
        if (isset($group['follow']['seasonId'])) {
            $seasonId = $group['follow']['seasonId'];
            $clientResponse = $this->apiGet("/v1/seasons/{$seasonId}");
            $data = json_decode($clientResponse->getBody(), true);
            if ($clientResponse->getStatusCode() >= 400) {
                return $this->view->render($response, 'admin/season/view.twig', ['errors' => $data['errors']]);
            }
            $season = $data['data'];
        
            $gridHtml = $this->container->get('scoring')->generate($group, $season, [])->getHtml();
        }


        return $this->view->render($response, 'group/view.twig', compact('group', 'groupId', 'member', 'season', 'gridHtml'));
    }

    /**
     * delete a group
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
            return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
        }

        return $response->withHeader('Location', "/dashboard")->withStatus(302);
    }

    /**
     * add player form
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function addPlayer(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        return $this->view->render($response, 'group/add-player.twig', compact('groupId', 'memberId'));
    }

    /**
     * submit add player form
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function addPlayerPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/groups/{$groupId}/member/{$memberId}/player", [
            'username' => $parsedBody['username'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/add-player.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
            ]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    /**
     * delete a player
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function deletePlayer(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}/player/{$playerId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    /**
     * follow form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function follow(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        // get seasons to get sports and season avaialble
        $clientResponse = $this->apiGet("/v1/seasons");
        $data = json_decode($clientResponse->getBody(), true);
        if ($clientResponse->getStatusCode() >= 400) {
        }
        $seasons = $data['data'];
        $seasons = collect($seasons)->groupBy('sport')->map(function($seasons) {
            return collect($seasons)->flatMap(function($season) {
                return [$season['seasonId'] => $season['name']];
            })->toArray();
        })->toArray();

        $sports = array_combine(array_keys($seasons), array_keys($seasons));

        return $this->view->render($response, 'group/follow.twig', compact('groupId', 'sports', 'seasons'));
    }

    /**
     * submit follow form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function followPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/groups/{$groupId}/follow", [
            'teamId' => $parsedBody['team_id'],
            'seasonId' => $parsedBody['season_id']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            $errors = $data['errors'];

            // get seasons to get sports and season avaialble
            $clientResponse = $this->apiGet("/v1/seasons");
            $data = json_decode($clientResponse->getBody(), true);
            if ($clientResponse->getStatusCode() >= 400) {
            }
            $seasons = $data['data'];
            $seasons = collect($seasons)->groupBy('sport')->map(function($seasons) {
                return collect($seasons)->flatMap(function($season) {
                    return [$season['seasonId'] => $season['name']];
                })->toArray();
            })->toArray();

            $sports = array_combine(array_keys($seasons), array_keys($seasons));

            return $this->view->render($response, 'group/follow.twig', [
                'errors' => $errors,
                'groupId' => $groupId,
                'sports' => $sports,
                'seasons' => $seasons
            ]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    /**
     * delete a follow
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function deleteFollow(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}/follow/{$followId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    /**
     * update member form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function updateMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'group/update-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
            ]);
        }

        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('memberId', $memberId);
        $memberTypes = ['Group-Admin' => 'Group-Admin', 'Group-Member' => 'Group-Member'];
        $allowMultiplePlayers = ['No', 'Yes'];

        return $this->view->render($response, 'group/update-member.twig', compact(
            'groupId',
            'memberId',
            'member',
            'memberTypes',
            'allowMultiplePlayers'
        ));
    }

    /**
     * submit update member form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function updateMemberPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('memberId', $memberId);
        $memberTypes = ['Group-Admin' => 'Group-Admin', 'Group-Member' => 'Group-Member'];
        $allowMultiplePlayers = ['No', 'Yes'];

        $clientResponse = $this->apiPatch("/v1/groups/{$groupId}/member/{$memberId}", [
            'groupId' => $groupId,
            'memberId' => $memberId,
            'groupRole' => $parsedBody['group_role'],
            'allowMultiple' => $parsedBody['allow_multiple']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'group/update-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
                'member' => $member,
                'memberTypes' => $memberTypes,
                'allowMultiplePlayers' => $allowMultiplePlayers
            ]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    /**
     * remove a member from the group
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function deleteMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}/member/{$memberId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    /** 
     * score form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function submitScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        return $this->view->render($response, 'group/submit-score.twig', compact('groupId', 'playerId'));
    }

    /**
     * submit score form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function submitScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/groups/{$groupId}/player/{$playerId}/score", [
            'gameId' => $parsedBody['game_id'],
            'homeTeamPrediction' => $parsedBody['home_team_prediction'],
            'awayTeamPrediction' => $parsedBody['away_team_prediction']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/submit-score.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'playerId' => $playerId,
            ]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    /**
     * update score form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function updateScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $score = collect($group['scores'])->firstWhere('scoreId', $scoreId);

        return $this->view->render($response, 'group/update-score.twig', compact('groupId', 'scoreId', 'score'));
    }

    /**
     * submit update score from
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function updateScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $score = collect($group['scores'])->firstWhere('scoreId', $scoreId);

        $clientResponse = $this->apiPatch("/v1/groups/{$groupId}/score/{$scoreId}", [
            'homeTeamPrediction' => $parsedBody['home_team_prediction'],
            'awayTeamPrediction' => $parsedBody['away_team_prediction']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/update-score.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'scoreId' => $scoreId,
                'score' => $score,
            ]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    /**
     * [deleteScore description]
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function deleteScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}/score/{$scoreId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }


















    /**
     * all groups for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminAll(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/admin/groups");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/group/index.twig');
        }

        $groups = $data['data'];
        return $this->view->render($response, 'admin/group/index.twig', compact('groups'));
    }

    /**
     * create group form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminCreate(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'admin/group/create.twig');
    }

    /**
     * submit the create group form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminCreatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/admin/groups", [
            'name' => $parsedBody['name'],
            'userId' => $parsedBody['user_id']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'admin/group/create.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', '/admin/groups')->withStatus(302);
    }

    /**
     * view a group for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminView(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $response->withHeader('Location', "/admin/groups")->withStatus(302);
        }

        $group = $data['data'];

        return $this->view->render($response, 'admin/group/view.twig', compact('group', 'groupId'));
    }

    /**
     * update group form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminUpdate(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/group/update.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
            ]);
        }

        $group = $data['data'];
        return $this->view->render($response, 'admin/group/update.twig', compact('group', 'groupId'));
    }

    /**
     * submit update group form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminUpdatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];

        $clientResponse = $this->apiPatch("/v1/admin/groups/{$groupId}", [
            'name' => $parsedBody['name'],
            'ownerId' => $group['ownerId'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'admin/group/update.twig', [
                'errors' => $data['errors'],
                'group' => $group,
                'groupId' => $groupId,
            ]);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /**
     * delete a group for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminDelete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $clientResponse = $this->apiDelete("/v1/admin/groups/{$groupId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
            return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
        }

        return $response->withHeader('Location', "/admin/groups")->withStatus(302);
    }

    /**
     * add member form for admin
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function adminAddMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $response->withHeader('Location', "/admin/groups")->withStatus(302);
        }

        $group = $data['data'];
        return $this->view->render($response, 'admin/group/add-member.twig', compact('groupId', 'group'));
    }

    /**
     * submit add member form for admin
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function adminAddMemberPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/admin/groups/{$groupId}/member", ['userId' => $parsedBody['user_id']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'admin/group/add-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
            ]);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /**
     * follow form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminFollow(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        // get seasons to get sports and season avaialble
        $clientResponse = $this->apiGet("/v1/seasons");
        $data = json_decode($clientResponse->getBody(), true);
        if ($clientResponse->getStatusCode() >= 400) {
        }
        $seasons = $data['data'];
        $seasons = collect($seasons)->groupBy('sport')->map(function($seasons) {
            return collect($seasons)->flatMap(function($season) {
                return [$season['seasonId'] => $season['name']];
            })->toArray();
        })->toArray();

        $sports = array_combine(array_keys($seasons), array_keys($seasons));

        return $this->view->render($response, 'admin/group/follow.twig', compact('groupId', 'sports', 'seasons'));
    }

    /**
     * submit follow form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminFollowPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/admin/groups/{$groupId}/follow", [
            'teamId' => $parsedBody['team_id'],
            'seasonId' => $parsedBody['season_id']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            $errors = $data['errors'];

            // get seasons to get sports and season avaialble
            $clientResponse = $this->apiGet("/v1/seasons");
            $data = json_decode($clientResponse->getBody(), true);
            if ($clientResponse->getStatusCode() >= 400) {
            }
            $seasons = $data['data'];
            $seasons = collect($seasons)->groupBy('sport')->map(function($seasons) {
                return collect($seasons)->flatMap(function($season) {
                    return [$season['seasonId'] => $season['name']];
                })->toArray();
            })->toArray();

            $sports = array_combine(array_keys($seasons), array_keys($seasons));

            return $this->view->render($response, 'admin/group/follow.twig', [
                'errors' => $errors,
                'groupId' => $groupId,
                'sports' => $sports,
                'seasons' => $seasons
            ]);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /**
     * delete a follow for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminDeleteFollow(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiDelete("/v1/admin/groups/{$groupId}/follow/{$followId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /**
     * update member form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminUpdateMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/group/update-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
            ]);
        }

        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('memberId', $memberId);
        $memberTypes = ['Group-Admin' => 'Group-Admin', 'Group-Member' => 'Group-Member'];
        $allowMultiplePlayers = ['No', 'Yes'];

        return $this->view->render($response, 'admin/group/update-member.twig', compact(
            'groupId',
            'memberId',
            'member',
            'memberTypes',
            'allowMultiplePlayers'
        ));
    }

    /**
     * submit update member form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminUpdateMemberPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('memberId', $memberId);
        $memberTypes = ['Group-Admin' => 'Group-Admin', 'Group-Member' => 'Group-Member'];
        $allowMultiplePlayers = ['No', 'Yes'];

        $clientResponse = $this->apiPatch("/v1/admin/groups/{$groupId}/member/{$memberId}", [
            'groupId' => $groupId,
            'memberId' => $memberId,
            'groupRole' => $parsedBody['group_role'],
            'allowMultiple' => $parsedBody['allow_multiple']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'admin/group/update-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
                'member' => $member,
                'memberTypes' => $memberTypes,
                'allowMultiplePlayers' => $allowMultiplePlayers
            ]);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /**
     * remove a member from the group for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminDeleteMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiDelete("/v1/admin/groups/{$groupId}/member/{$memberId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /**
     * add player form for admin
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function adminAddPlayer(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        return $this->view->render($response, 'admin/group/add-player.twig', compact('groupId', 'memberId'));
    }

    /**
     * submit add player form for admin
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function adminAddPlayerPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/admin/groups/{$groupId}/member/{$memberId}/player", [
            'username' => $parsedBody['username'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'admin/group/add-player.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'memberId' => $memberId,
            ]);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /**
     * delete a player for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminDeletePlayer(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiDelete("/v1/admin/groups/{$groupId}/player/{$playerId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /** 
     * score form for admin for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminSubmitScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        return $this->view->render($response, 'admin/group/submit-score.twig', compact('groupId', 'playerId'));
    }

    /**
     * submit score form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminSubmitScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/admin/groups/{$groupId}/player/{$playerId}/score", [
            'gameId' => $parsedBody['game_id'],
            'homeTeamPrediction' => $parsedBody['home_team_prediction'],
            'awayTeamPrediction' => $parsedBody['away_team_prediction']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'admin/group/submit-score.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'playerId' => $playerId,
            ]);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /**
     * update score form for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminUpdateScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiGet("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $score = collect($group['scores'])->firstWhere('scoreId', $scoreId);

        return $this->view->render($response, 'admin/group/update-score.twig', compact('groupId', 'scoreId', 'score'));
    }

    /**
     * submit update score from for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminUpdateScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $score = collect($group['scores'])->firstWhere('scoreId', $scoreId);

        $clientResponse = $this->apiPatch("/v1/admin/groups/{$groupId}/score/{$scoreId}", [
            'homeTeamPrediction' => $parsedBody['home_team_prediction'],
            'awayTeamPrediction' => $parsedBody['away_team_prediction']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'admin/group/update-score.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'scoreId' => $scoreId,
                'score' => $score,
            ]);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }

    /**
     * delete a score for admin
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminDeleteScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);

        $clientResponse = $this->apiDelete("/v1/admin/groups/{$groupId}/score/{$scoreId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', "/admin/groups/{$groupId}")->withStatus(302);
    }
}