<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TeamController extends AbstractController
{
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet('/v1/teams');

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'team/index.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $data = json_decode($clientResponse->getBody(), true);
        $teams = $data['data'];

        return $this->view->render($response, 'team/index.twig', compact('teams'));
    }

    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $teamId = $args['teamId'];

        $clientResponse = $this->apiGet('/v1/teams/' . $teamId);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            
            return $this->view->render($response, 'team/view.twig', [
                'errors' => $data['errors'],
            ]);
        }

        $data = json_decode($clientResponse->getBody(), true);
        $team = $data['data'];

        return $this->view->render($response, 'team/view.twig', compact('team'));
    }

    public function add(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'team/add.twig');
    }

    public function addPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/v1/teams', [
            'designation' => $parsedBody['designation'],
            'mascot' => $parsedBody['mascot']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'team/add.twig', [
                'errors' => $data['errors'],
            ]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    public function follow(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'team/follow.twig');

    }

    public function followPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost('/v1/teams/follow', [
            'group_id' => $parsedBody['group_id'],
            'team_id' => $parsedBody['team_id']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'team/follow.twig', [
                'errors' => $data['errors'],
            ]);
        }

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
}