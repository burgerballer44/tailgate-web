<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// follow form
class FollowAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {   
        extract($this->args);

        if ('POST' != $this->request->getMethod()) {
            // get seasons to get sports and season avaialble
            $clientResponse = $this->apiClient->get("/v1/seasons");
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

            return $this->view->render($this->response, 'group/follow.twig', compact('groupId', 'sports', 'seasons'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->post("/v1/groups/{$groupId}/follow", [
            'teamId' => $parsedBody['team_id'],
            'seasonId' => $parsedBody['season_id']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            $errors = $data['errors'];

            // get seasons to get sports and season avaialble
            $clientResponse = $this->apiClient->get("/v1/seasons");
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

            return $this->view->render($this->response, 'group/follow.twig', [
                'errors' => $errors,
                'groupId' => $groupId,
                'sports' => $sports,
                'seasons' => $seasons
            ]);
        }

        return $this->response->withHeader('Location', "/group/{$groupId}")->withStatus(302);
    }
}