<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// create season form
class CreateSeasonAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $sports = ['Football' => 'Football', 'Basketball' => 'Basketball'];
        $seasonTypes = ['Regular-Season' => 'Regular-Season'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/season/create.twig', compact('sports', 'seasonTypes'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->post("/v1/admin/seasons", [
            'name' => $parsedBody['name'],
            'sport' => $parsedBody['sport'],
            'seasonType' => $parsedBody['season_type'],
            'seasonStart' => $parsedBody['season_start'],
            'seasonEnd' => $parsedBody['season_end']
        ]);
        $data = $apiResponse->getData();

       if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/season/create.twig', [
                'errors' => $data['errors'],
                'sports' => $sports, 
                'seasonTypes' => $seasonTypes
            ]);
       }

       return $this->response->withHeader('Location', '/admin/season')->withStatus(302);
    }
}