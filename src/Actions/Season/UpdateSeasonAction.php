<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update season form
class UpdateSeasonAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $sports = ['Football' => 'Football', 'Basketball' => 'Basketball'];
        $seasonTypes = ['Regular-Season' => 'Regular-Season'];
            
        extract($this->args);

        if ('POST' != $this->request->getMethod()) {

            $clientResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
            $data = json_decode($clientResponse->getBody(), true);

            if ($clientResponse->getStatusCode() >= 400) {
                return $this->view->render($this->response, 'admin/season/update.twig', ['errors' => $data['errors']]);
            }

            $season = $data['data'];

            return $this->view->render($this->response, 'admin/season/update.twig', compact('season', 'seasonId', 'sports', 'seasonTypes'));
        }

        $parsedBody = $this->request->getParsedBody();

        $clientResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);
        $season = $data['data'];

        $sports = ['Football' => 'Football', 'Basketball' => 'Basketball'];
        $seasonTypes = ['Regular-Season' => 'Regular-Season'];

        $clientResponse = $this->apiClient->patch("/v1/admin/seasons/{$seasonId}", [
            'sport' => $parsedBody['sport'],
            'seasonType' => $parsedBody['season_type'],
            'name' => $parsedBody['name'],
            'seasonStart' => $parsedBody['season_start'],
            'seasonEnd' => $parsedBody['season_end']
        ]);

        if ($clientResponse->getStatusCode() >= 400) {
            $data = json_decode($clientResponse->getBody(), true);

            return $this->view->render($this->response, 'admin/season/update.twig', [
                'errors' => $data['errors'],
                'season' => $season,
                'seasonId' => $seasonId,
                'sports' => $sports,
                'seasonTypes' => $seasonTypes
            ]);
        }

        return $this->response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }
}