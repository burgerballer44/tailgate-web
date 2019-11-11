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

        $clientResponse = $this->apiPost("/v1/groups", ['name' => $parsedBody['name']]);

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
        extract($args);

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
        
        return $this->view->render($response, 'group/view.twig', compact('group', 'groupId', 'member'));
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
















    // update member form
    public function updateMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];
        $memberId = $args['memberId'];

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
        return $this->view->render($response, 'group/update-member.twig', compact('groupId', 'memberId', 'member'));
    }

    // submit update member form
    public function updateMemberPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];
        $memberId = $args['memberId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('memberId', $memberId);

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
            ]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    public function deleteMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];
        $memberId = $args['memberId'];

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}/member/{$memberId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/view.twig', ['errors' => $data['errors'], 'groupId' => $groupId]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    // score form
    public function submitScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];
        $playerId = $args['playerId'];
        return $this->view->render($response, 'group/submit-score.twig', compact('groupId', 'playerId'));
    }

    // submit score form
    public function submitScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];
        $playerId = $args['playerId'];
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

    public function updateScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];
        $scoreId = $args['scoreId'];

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];
        $score = collect($group['scores'])->firstWhere('scoreId', $scoreId);

        return $this->view->render($response, 'group/update-score.twig', compact('groupId', 'scoreId', 'score'));
    }

    public function updateScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];
        $scoreId = $args['scoreId'];
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

    public function deleteScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];
        $scoreId = $args['scoreId'];

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}/score/{$scoreId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/view.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
            ]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    // add member form
    public function addMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        $group = $data['data'];
        return $this->view->render($response, 'group/add-member.twig', compact('groupId', 'group'));
    }

    // submit add member form
    public function addMemberPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/groups/{$groupId}/member", ['userId' => $parsedBody['user_id']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/add-member.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
            ]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    // update group form
    public function update(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'group/update.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
            ]);
        }

        $group = $data['data'];
        return $this->view->render($response, 'group/update.twig', compact('group', 'groupId'));
    }


    // submit update group form
    public function updatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];

        $clientResponse = $this->apiPatch("/v1/groups/{$groupId}", ['name' => $parsedBody['name']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/update.twig', [
                'errors' => $data['errors'],
                'group' => $group,
                'groupId' => $groupId,
            ]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}