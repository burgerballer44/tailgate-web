<?php

namespace TailgateWeb\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TeamController extends AbstractController
{
    /**
     * view all teams
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function all(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $clientResponse = $this->apiGet("/v1/teams");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {            
            return $this->view->render($response, 'admin/team/index.twig', ['errors' => $data['errors']]);
        }

        $teams = $data['data'];
        return $this->view->render($response, 'admin/team/index.twig', compact('teams'));
    }

    /**
     * view a team, its follows, and games
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function view(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/teams/{$teamId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/team/view.twig', ['errors' => $data['errors']]);
        }

        $team = $data['data'];
        return $this->view->render($response, 'admin/team/view.twig', compact('team'));
    }

    /**
     * add team form
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function add(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        return $this->view->render($response, 'admin/team/add.twig');
    }

    /**
     * submit add team form
     * @param ServerRequestInterface $request  [description]
     * @param ResponseInterface      $response [description]
     * @param [type]                 $args     [description]
     */
    public function addPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiPost("/v1/admin/teams", [
            'designation' => $parsedBody['designation'],
            'mascot' => $parsedBody['mascot']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'admin/team/add.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', '/admin/team')->withStatus(302);
    }

    /**
     * update team form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);

        $clientResponse = $this->apiGet("/v1/teams/{$teamId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($response, 'admin/team/update.twig', ['errors' => $data['errors']]);
        }

        $team = $data['data'];
        return $this->view->render($response, 'admin/team/update.twig', compact('team'));
    }

    /**
     * submit update team form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function updatePost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiGet("/v1/teams/{$teamId}");
        $data = json_decode($clientResponse->getBody(), true);
        $team = $data['data'];

        $clientResponse = $this->apiPatch("/v1/admin/teams/{$teamId}", [
            'designation' => $parsedBody['designation'],
            'mascot' => $parsedBody['mascot']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($response, 'admin/team/update.twig', [
                'errors' => $data['errors'],
                'teamId' => $teamId,
                'team' => $team
            ]);
        }

        return $response->withHeader('Location', "/admin/team/{$teamId}")->withStatus(302);
    }

    /**
     * delete team
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, $args)
    {   
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiDelete("/v1/admin/teams/{$teamId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
        }

        return $response->withHeader('Location', '/admin/team')->withStatus(302);
    }

    /**
     * admin follow form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminFollow(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        return $this->view->render($response, 'admin/team/follow.twig', compact('teamId'));
    }

    /**
     * submit admin follow form
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminFollowPost(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $groupId = $parsedBody['group_id'];
        $seasonId = $parsedBody['season_id'];

        $clientResponse = $this->apiPost("/v1/groups/{$groupId}/follow", [
            'teamId' => $teamId,
            'seasonId' => $seasonId
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            return $this->view->render($response, 'admin/team/follow.twig', ['errors' => $data['errors'],'teamId' => $teamId]);
        }

        return $response->withHeader('Location', "/admin/team/{$teamId}")->withStatus(302);
    }

    /**
     * delete a follow
     * @param  ServerRequestInterface $request  [description]
     * @param  ResponseInterface      $response [description]
     * @param  [type]                 $args     [description]
     * @return [type]                           [description]
     */
    public function adminDeleteFollow(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        extract($args);
        $parsedBody = $request->getParsedBody();

        $clientResponse = $this->apiDelete("/v1/groups/{$groupId}/follow/{$followId}");

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);
            $this->flash->addMessage('error', $data['errors']);
            return $this->view->render($response, 'admin/team/view.twig', ['errors' => $data['errors']]);
        }

        return $response->withHeader('Location', "/admin/team/{$teamId}")->withStatus(302);
    }
}