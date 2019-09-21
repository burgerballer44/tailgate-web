<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TeamController extends AbstractController
{
    // view all teams
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/teams");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {            
            return $this->view->render($response, 'team/index.twig', ['errors' => $data['errors']]);
        }

        $teams = $data['data'];
        return $this->view->render($response, 'team/index.twig', compact('teams'));
    }

    // view a team, its follows, and games
    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $teamId = $args['teamId'];

        $clientResponse = $this->apiGet("/v1/teams/{$teamId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'team/view.twig', ['errors' => $data['errors']]);
        }

        $team = $data['data'];
        return $this->view->render($response, 'team/view.twig', compact('team'));
    }

    // add team form
    public function add(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'team/add.twig');
    }

    // submit add team form
    public function addPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/teams", [
            'designation' => $parsedBody['designation'],
            'mascot' => $parsedBody['mascot']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'team/add.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', '/team')->withStatus(302);
    }

    // update team form
    public function update(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $teamId = $args['teamId'];

        $clientResponse = $this->apiGet("/v1/teams/{$teamId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'team/update.twig', ['errors' => $data['errors']]);
        }

        $team = $data['data'];
        return $this->view->render($response, 'team/update.twig', compact('team'));
    }

    // submit update team form
    public function updatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $teamId = $args['teamId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/teams/{$teamId}");
        $data = json_decode($clientResponse->getBody(), true);
        $team = $data['data'];

        $clientResponse = $this->apiPatch("/v1/teams/{$teamId}", [
            'designation' => $parsedBody['designation'],
            'mascot' => $parsedBody['mascot']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'team/update.twig', [
                'errors' => $data['errors'],
                'teamId' => $teamId,
                'team' => $team
            ]);
        }

        return $response->withHeader('Location', "/team/{$teamId}")->withStatus(302);
    }

    // delete team
    public function delete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        $teamId = $args['teamId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiDelete("/v1/teams/{$teamId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'team/update.twig', ['errors' => $data['errors'],'teamId' => $teamId]);
        }

        return $response->withHeader('Location', '/team')->withStatus(302);
    }

    // follow form
    public function follow(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $teamId = $args['teamId'];
        return $this->view->render($response, 'team/follow.twig', compact('teamId'));
    }

    // submit follow form
    public function followPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $teamId = $args['teamId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/teams/{$teamId}/follow", [
            'teamId' => $teamId,
            'groupId' => $parsedBody['group_id']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'team/follow.twig', ['errors' => $data['errors'],'teamId' => $teamId]);
        }

        return $response->withHeader('Location', "/team/{$teamId}")->withStatus(302);
    }

    // delete a follow
    public function deleteFollow(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $teamId = $args['teamId'];
        $followId = $args['followId'];
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiDelete("/v1/teams/{$teamId}/follow/{$followId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'team/view.twig', ['errors' => $data['errors'],'teamId' => $teamId]);
        }

        return $response->withHeader('Location', "/team/{$teamId}")->withStatus(302);
    }
}