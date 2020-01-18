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

        // get season
        $apiResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/season/update.twig', ['errors' => $data['errors']]);
        }
        $season = $data['data'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/season/update.twig', compact('season', 'seasonId', 'sports', 'seasonTypes'));
        }

        $parsedBody = $this->request->getParsedBody();

        $sports = ['Football' => 'Football', 'Basketball' => 'Basketball'];
        $seasonTypes = ['Regular-Season' => 'Regular-Season'];

        $apiResponse = $this->apiClient->patch("/v1/admin/seasons/{$seasonId}", [
            'sport' => $parsedBody['sport'],
            'seasonType' => $parsedBody['season_type'],
            'name' => $parsedBody['name'],
            'seasonStart' => $parsedBody['season_start'],
            'seasonEnd' => $parsedBody['season_end']
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
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