<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GroupController extends AbstractController
{
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet('/v1/groups');

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'group/index.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $data = json_decode($clientResponse->getBody(), true);
        $groups = $data['data'];

        return $this->view->render($response, 'group/index.twig', compact('groups'));
    }

    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];

        $clientResponse = $this->apiGet('/v1/groups/' . $groupId);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'group/view.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $data = json_decode($clientResponse->getBody(), true);
        $group = $data['data'];

        return $this->view->render($response, 'group/view.twig', compact('group'));
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'group/create.twig');
    }

    public function createPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/v1/groups', [
            'name' => $parsedBody['name'],
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/create.twig', [
                'errors' => $data['errors'],
            ]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function addMember(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];
        return $this->view->render($response, 'group/add-member.twig', compact('groupId'));
    }

    public function addMemberPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/v1/groups/member', [
            'group_id' => $groupId,
            'user_id' => $parsedBody['user_id']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/add-member.twig', [
                'errors' => $data['errors'],
                'group_id' => $groupId,
            ]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function submitScore(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $groupId = $args['groupId'];
        $playerId = $args['playerId'];
        return $this->view->render($response, 'group/submit-score.twig', compact('groupId', 'playerId'));
    }

    public function submitScorePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $groupId = $args['groupId'];
        $playerId = $args['playerId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/v1/groups/member', [
            'group_id' => $groupId,
            'playerId' => $playerId,
            'game_id' => $parsedBody['game_id'],
            'home_team_prediction' => $parsedBody['home_team_prediction'],
            'away_team_prediction' => $parsedBody['away_team_prediction']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'group/submit-score.twig', [
                'errors' => $data['errors'],
                'groupId' => $groupId,
                'playerId' => $playerId,
            ]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}