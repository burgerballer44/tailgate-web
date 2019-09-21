<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GroupController extends AbstractController
{
    // view all groups
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/groups");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'group/index.twig', ['errors' => $data['errors']]);
        }

        $groups = $data['data'];
        return $this->view->render($response, 'group/index.twig', compact('groups'));
    }

    // view a group
    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];

        $clientResponse = $this->apiGet("/v1/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'group/view.twig', ['errors' => $data['errors'], 'groupId' => $groupId]);
        }

        $group = $data['data'];
        return $this->view->render($response, 'group/view.twig', compact('group', 'groupId'));
    }

    // create group form
    public function create(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'group/create.twig');
    }

    // submit create group form
    public function createPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/groups", ['name' => $parsedBody['name']]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/create.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', '/group')->withStatus(302);
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

    // delete a group
    public function delete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'group/update.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', "/group")->withStatus(302);
    }

    // add member form
    public function addMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];
        return $this->view->render($response, 'group/add-member.twig', compact('groupId'));
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

            return $this->view->render($response, 'group/view.twig', ['errors' => $data['errors'],'groupId' => $groupId]);
        }

        return $response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }

    // add player form
    public function addPlayer(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];
        $memberId = $args['memberId'];
        return $this->view->render($response, 'group/add-player.twig', compact('groupId', 'memberId'));
    }

    // submit add player form
    public function addPlayerPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];
        $memberId = $args['memberId'];
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

    // delete a player
    public function deletePlayer(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];
        $playerId = $args['playerId'];

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}/player/{$playerId}");

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
}